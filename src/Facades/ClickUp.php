<?php

declare(strict_types=1);

namespace Maya719\ClickUp\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Maya719\ClickUp\Resources\Tasks tasks()
 * @method static \Maya719\ClickUp\Resources\Lists lists()
 * @method static \Maya719\ClickUp\Resources\Folders folders()
 * @method static \Maya719\ClickUp\Resources\Spaces spaces()
 * @method static \Maya719\ClickUp\Resources\Workspaces workspaces()
 * @method static \Maya719\ClickUp\Resources\Workspaces teams()
 * @method static \Maya719\ClickUp\Resources\Comments comments()
 * @method static \Maya719\ClickUp\Resources\Checklists checklists()
 * @method static \Maya719\ClickUp\Resources\CustomFields customFields()
 * @method static \Maya719\ClickUp\Resources\Tags tags()
 * @method static \Maya719\ClickUp\Resources\Attachments attachments()
 * @method static \Maya719\ClickUp\Resources\Dependencies dependencies()
 * @method static \Maya719\ClickUp\Resources\Goals goals()
 * @method static \Maya719\ClickUp\Resources\Views views()
 * @method static \Maya719\ClickUp\Resources\TimeTracking timeTracking()
 * @method static \Maya719\ClickUp\Resources\Webhooks webhooks()
 * @method static \Maya719\ClickUp\Resources\Members members()
 * @method static \Maya719\ClickUp\Resources\Users users()
 * @method static \Maya719\ClickUp\Resources\Guests guests()
 * @method static \Maya719\ClickUp\Resources\UserGroups userGroups()
 * @method static \Maya719\ClickUp\Resources\AuthorizedUser authorizedUser()
 * @method static \Maya719\ClickUp\Authorization\OAuth oauth(string $clientId, string $clientSecret)
 * @method static \Maya719\ClickUp\Http\Client http()
 * @method static array createTask(array $data, string|null $listId = null)
 * @method static array listTasks(array $query = [], string|null $listId = null)
 * @method static array rateLimit()
 * @method static \Maya719\ClickUp\ClickUp withToken(string $token, bool|null $isOAuth = null)
 * @method static string|null defaultListId()
 * @method static \Maya719\ClickUp\ClickUp setDefaultListId(string|null $listId)
 *
 * @see \Maya719\ClickUp\ClickUp
 */
class ClickUp extends Facade
{
    /**
     * Get the registered name of the component in the container.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'clickup';
    }
}
