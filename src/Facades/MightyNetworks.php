<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Facades;

use Illuminate\Support\Facades\Facade;
use MCKLtech\MightyNetworks\Connectors\AdminConnector;
use MCKLtech\MightyNetworks\Connectors\GraphQLConnector;
use MCKLtech\MightyNetworks\GraphQL\GraphQLClient;
use MCKLtech\MightyNetworks\MightyNetworks as Connection;
use MCKLtech\MightyNetworks\MightyNetworksManager;
use MCKLtech\MightyNetworks\Resources\AbuseReportsResource;
use MCKLtech\MightyNetworks\Resources\AssetsResource;
use MCKLtech\MightyNetworks\Resources\BadgesResource;
use MCKLtech\MightyNetworks\Resources\CollectionsResource;
use MCKLtech\MightyNetworks\Resources\CommentsResource;
use MCKLtech\MightyNetworks\Resources\CustomFieldsResource;
use MCKLtech\MightyNetworks\Resources\EventsResource;
use MCKLtech\MightyNetworks\Resources\InvitesResource;
use MCKLtech\MightyNetworks\Resources\MembersResource;
use MCKLtech\MightyNetworks\Resources\NetworkResource;
use MCKLtech\MightyNetworks\Resources\PlansResource;
use MCKLtech\MightyNetworks\Resources\PollsResource;
use MCKLtech\MightyNetworks\Resources\PostsResource;
use MCKLtech\MightyNetworks\Resources\PurchasesResource;
use MCKLtech\MightyNetworks\Resources\SpacesResource;
use MCKLtech\MightyNetworks\Resources\SubscriptionsResource;
use MCKLtech\MightyNetworks\Resources\TagsResource;

/**
 * @method static Connection connection(string|null $name = null)
 * @method static Connection driver(string|null $driver = null)
 * @method static Connection withCredentials(string|null $adminToken, int|string $networkId, array<string, mixed> $overrides = [], string|null $basedOn = null)
 * @method static Connection withConfig(array<string, mixed> $config, string $name = 'runtime')
 * @method static string getDefaultDriver()
 * @method static AdminConnector admin()
 * @method static GraphQLConnector graphql()
 * @method static GraphQLClient graphqlClient()
 * @method static NetworkResource network()
 * @method static MembersResource members()
 * @method static PostsResource posts()
 * @method static CommentsResource comments()
 * @method static SpacesResource spaces()
 * @method static CollectionsResource collections()
 * @method static EventsResource events()
 * @method static PlansResource plans()
 * @method static SubscriptionsResource subscriptions()
 * @method static PurchasesResource purchases()
 * @method static InvitesResource invites()
 * @method static TagsResource tags()
 * @method static BadgesResource badges()
 * @method static CustomFieldsResource customFields()
 * @method static PollsResource polls()
 * @method static AssetsResource assets()
 * @method static AbuseReportsResource abuseReports()
 *
 * @see MightyNetworksManager
 */
final class MightyNetworks extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return MightyNetworksManager::class;
    }
}
