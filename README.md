# ClickUp PHP SDK

A PHP SDK and Laravel package for the [ClickUp API v2](https://developer.clickup.com/reference).

- Typed exceptions instead of raw HTTP status codes
- Automatic retries that honour ClickUp's rate limit headers
- Lazy cursors for the paginated task endpoints
- Laravel service provider, facade, webhook receiver route and signature verification
- Works fine outside Laravel as a plain PHP SDK

## Installation

```bash
composer require maya719/clickup
```

Publish the config file (Laravel only):

```bash
php artisan vendor:publish --tag=clickup-config
```

## Configuration

```dotenv
CLICKUP_API_KEY=pk_12345_ABCDEF
CLICKUP_LIST_ID=901300000000        # optional default List
CLICKUP_WEBHOOK_SECRET=...          # returned when you create a webhook

# Only if your app performs the OAuth flow for other ClickUp users
CLICKUP_CLIENT_ID=...
CLICKUP_CLIENT_SECRET=...
CLICKUP_REDIRECT_URI=https://example.com/clickup/callback
```

Find your personal token in ClickUp under **Settings → Apps**.

## Usage

### Laravel

```php
use Maya719\ClickUp\Facades\ClickUp;

$tasks = ClickUp::tasks()->all('901300000000');

$task = ClickUp::tasks()->create('901300000000', [
    'name' => 'Write the release notes',
    'description' => 'Cover every change since 1.2.',
    'assignees' => [183],
    'priority' => 2,
    'due_date' => now()->addDays(3)->getTimestampMs(),
]);
```

Type-hint the client anywhere the container resolves dependencies:

```php
use Maya719\ClickUp\ClickUp;

public function __construct(private readonly ClickUp $clickup) {}
```

### Plain PHP

```php
use Maya719\ClickUp\ClickUp;

$clickup = new ClickUp('pk_12345_ABCDEF');

foreach ($clickup->workspaces()->all() as $workspace) {
    echo $workspace['name'], PHP_EOL;
}
```

### Paging

`all()` returns a single page (ClickUp serves 100 tasks per page). `cursor()`
walks every page lazily and yields one task at a time:

```php
foreach ($clickup->tasks()->cursor('901300000000', ['subtasks' => true]) as $task) {
    // One request per page, fetched only as you iterate.
}
```

`page()` returns the raw response if you want the `last_page` flag yourself.

### Available resources

| Accessor | Covers |
| --- | --- |
| `tasks()` | list, get, create, update, delete, workspace-wide filters, time in status, templates, merge |
| `lists()` | Lists in a Folder, folderless Lists, members, templates, tasks in multiple Lists |
| `folders()` | CRUD plus creation from a Folder template |
| `spaces()` | CRUD |
| `workspaces()` / `teams()` | Workspaces, seats, plan, custom roles, shared hierarchy, task templates |
| `comments()` | task, List and View comments, plus threaded replies |
| `checklists()` | checklists and checklist items |
| `customFields()` | accessible fields, set and clear values |
| `tags()` | Space tags, and tagging tasks |
| `attachments()` | upload from a path, a string or a stream |
| `dependencies()` | blocking/waiting-on dependencies and task links |
| `goals()` | Goals and Key Results |
| `views()` | Views at every level, and the tasks inside them |
| `timeTracking()` | time entries, running timer, start/stop, history, labels |
| `webhooks()` | create, list, update, delete |
| `members()` | task and List members |
| `users()`, `guests()`, `userGroups()` | Workspace membership (Enterprise for users/guests) |
| `authorizedUser()` | the token's own user and Workspaces |

Anything not wrapped yet is still reachable through the transport:

```php
$clickup->http()->get('team/123/custom_item');
```

### Custom fields

```php
$fields = $clickup->customFields()->forList('901300000000');

$clickup->customFields()->set($taskId, $fieldId, 'In review');

// Labels and relationships take add/rem instructions
$clickup->customFields()->set($taskId, $labelFieldId, [
    'add' => ['option-uuid-1'],
    'rem' => ['option-uuid-2'],
]);
```

### Error handling

Every failure throws a subclass of `ClickUpException`:

| Exception | HTTP |
| --- | --- |
| `AuthenticationException` | 401 |
| `AuthorizationException` | 403 |
| `NotFoundException` | 404 |
| `ValidationException` | 400, 422 |
| `RateLimitException` | 429 |
| `ServerException` | 5xx |
| `TransportException` | the request never completed |

```php
use Maya719\ClickUp\Exceptions\NotFoundException;
use Maya719\ClickUp\Exceptions\RateLimitException;

try {
    $task = $clickup->tasks()->get('abc123');
} catch (NotFoundException) {
    // No such task.
} catch (RateLimitException $e) {
    // $e->retryAfter(), $e->limit(), $e->remaining(), $e->resetsAt()
}
```

`$e->errorCode()` exposes ClickUp's `ECODE` and `$e->context()` the full payload.

### Rate limits

429s and 5xx responses are retried automatically (twice by default, configurable
with `CLICKUP_RETRIES`). Retries wait for the window ClickUp advertises via
`Retry-After` / `X-RateLimit-Reset` and otherwise back off exponentially. Read the
current budget at any time:

```php
$clickup->rateLimit(); // ['limit' => 100, 'remaining' => 96, 'reset' => 1700000060]
```

## OAuth

Use OAuth when your app acts on behalf of other ClickUp users. ClickUp's access
tokens do not expire and no refresh token is issued, so store the token you get
back and reuse it.

```php
use Maya719\ClickUp\ClickUp;

$oauth = (new ClickUp())->oauth(config('clickup.oauth.client_id'), config('clickup.oauth.client_secret'));

// 1. Send the user to ClickUp.
return redirect($oauth->authorizationUrl(config('clickup.oauth.redirect_uri'), $state));

// 2. Exchange the code ClickUp redirects back with.
$token = $oauth->accessTokenString($request->query('code'));

// 3. Use it.
$clickup = new ClickUp($token, isOAuth: true);
```

## Webhooks

Register a webhook and keep the `secret` from the response:

```php
$webhook = $clickup->webhooks()->create(
    workspaceId: '123456',
    endpoint: 'https://example.com/clickup/webhook',
    events: ['taskCreated', 'taskStatusUpdated'],
    options: ['list_id' => '901300000000'],   // optional scope
);

$webhook['webhook']['secret']; // -> CLICKUP_WEBHOOK_SECRET
```

`Webhooks::events()` returns every event name ClickUp can send.

### Built-in receiver (Laravel)

Enable the packaged route and the package will verify the signature and dispatch
an event for each delivery:

```dotenv
CLICKUP_WEBHOOK_ROUTE_ENABLED=true
CLICKUP_WEBHOOK_ROUTE_PATH=clickup/webhook
```

```php
use Illuminate\Support\Facades\Event;
use Maya719\ClickUp\Events\WebhookReceived;

Event::listen(function (WebhookReceived $event) {
    if ($event->event === 'taskStatusUpdated') {
        $event->taskId();
        $event->historyItems();
        $event->payload;
    }
});
```

Deliveries with a missing or wrong `X-Signature` get a 403 and no event.

### Verifying by hand

If you prefer your own route, use the verifier directly — and check the **raw**
body, since re-encoding a decoded payload changes the bytes:

```php
use Maya719\ClickUp\Webhooks\SignatureVerifier;

$verifier = app(SignatureVerifier::class);

if (! $verifier->verify($request->getContent(), $request->header('X-Signature'))) {
    abort(403);
}
```

Or apply `Maya719\ClickUp\Http\Middleware\VerifyClickUpWebhookSignature` to your route.

## Testing

The transport takes any Guzzle client, so tests can drive it with a mock handler:

```php
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Maya719\ClickUp\ClickUp;
use Maya719\ClickUp\Http\Client;

$mock = new MockHandler([new Response(200, [], json_encode(['tasks' => []]))]);

$clickup = ClickUp::fromClient(new Client(
    token: 'pk_test',
    http: new GuzzleClient(['handler' => HandlerStack::create($mock)]),
));
```

Run the package's own suite with:

```bash
composer test
```

## Notes

- ClickUp expresses every date as a Unix timestamp in **milliseconds**.
- Pass `['custom_task_ids' => true, 'team_id' => $workspaceId]` as the query on
  task endpoints to address tasks by their custom id.
- ClickUp's API calls a Workspace a "team"; `workspaces()` and `teams()` are the
  same resource.

## License

MIT. See [LICENSE](LICENSE).
