<?php

declare(strict_types=1);

namespace App\Providers;

use App\Core\DatabaseManager;
use App\Core\Database\BaseQueryBuilder;
use App\Core\Database\DatabaseConnectionInterface;
use App\Core\Database\QueryBuilderInterface;
use App\Core\ServiceProvider;
use App\Exceptions\ExceptionHandler;
use App\Http\Kernel;
use App\Http\Request;
use App\Modules\Franchise\Controllers\FranchiseController;
use App\Modules\Franchise\Repositories\FranchiseRepository;
use App\Modules\Franchise\Services\FranchiseService;
use App\Modules\Franchise\Validators\FranchiseValidator;
use App\Modules\Authentication\Controllers\AuthenticationController;
use App\Modules\Authentication\Controllers\AuthContextController;
use App\Modules\Authentication\Middleware\AuthenticationMiddleware;
use App\Modules\Authentication\Repositories\AuthTokenRepository;
use App\Modules\Authentication\Services\AuthenticationService;
use App\Modules\Organization\Controllers\OrganizationController;
use App\Modules\Organization\Repositories\OrganizationRepository;
use App\Modules\Organization\Services\OrganizationService;
use App\Modules\Organization\Validators\OrganizationValidator;
use App\Modules\PartnerAgency\Controllers\PartnerAgencyController;
use App\Modules\PartnerAgency\Repositories\PartnerAgencyRepository;
use App\Modules\PartnerAgency\Services\PartnerAgencyService;
use App\Modules\PartnerAgency\Validators\PartnerAgencyValidator;
use App\Modules\Position\Controllers\PositionController;
use App\Modules\Position\Repositories\PositionRepository;
use App\Modules\Position\Services\PositionService;
use App\Modules\Position\Validators\PositionValidator;
use App\Modules\Permission\Controllers\PermissionController;
use App\Modules\Permission\Controllers\PositionPermissionController;
use App\Modules\Permission\Repositories\PermissionRepository;
use App\Modules\Permission\Repositories\PositionPermissionRepository;
use App\Modules\Permission\Services\PermissionService;
use App\Modules\Permission\Services\PositionPermissionService;
use App\Modules\Authorization\Middleware\AuthorizationMiddleware;
use App\Modules\Authorization\Services\AuthorizationService;
use App\Modules\Authorization\Services\OrganizationScopeService;
use App\Modules\User\Controllers\UserController;
use App\Modules\User\Repositories\UserRepository;
use App\Modules\User\Services\UserService;
use App\Modules\User\Validators\UserValidator;
use App\Routing\Router;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(Logger::class, function (): Logger {
            $logger = new Logger((string) $this->config['app']['name']);
            $logger->pushHandler(new StreamHandler(
                (string) $this->config['app']['log']['path'],
                (int) $this->config['app']['log']['level']
            ));

            return $logger;
        });

        $this->container->singleton(DatabaseManager::class, function (): DatabaseManager {
            return new DatabaseManager($this->config['database']);
        });

        $this->container->singleton(
            DatabaseConnectionInterface::class,
            fn (): DatabaseConnectionInterface => $this->container->make(DatabaseManager::class)
        );

        $this->container->bind(BaseQueryBuilder::class);
        $this->container->bind(QueryBuilderInterface::class, BaseQueryBuilder::class);

        $this->container->singleton(Kernel::class);
        $this->container->singleton(
            Request::class,
            fn (): Request => $this->container->make(Kernel::class)->createRequest()
        );

        $this->container->bind(OrganizationRepository::class);
        $this->container->bind(OrganizationValidator::class);
        $this->container->bind(OrganizationService::class);
        $this->container->bind(OrganizationController::class);

        $this->container->bind(FranchiseRepository::class);
        $this->container->bind(FranchiseValidator::class);
        $this->container->bind(FranchiseService::class);
        $this->container->bind(FranchiseController::class);

        $this->container->bind(PartnerAgencyRepository::class);
        $this->container->bind(PartnerAgencyValidator::class);
        $this->container->bind(PartnerAgencyService::class);
        $this->container->bind(PartnerAgencyController::class);

        $this->container->bind(UserRepository::class);
        $this->container->bind(UserValidator::class);
        $this->container->bind(UserService::class);
        $this->container->bind(UserController::class);

        $this->container->bind(PositionRepository::class);
        $this->container->bind(PositionValidator::class);
        $this->container->bind(PositionService::class);
        $this->container->bind(PositionController::class);

        $this->container->bind(PermissionRepository::class);
        $this->container->bind(PositionPermissionRepository::class);
        $this->container->bind(PermissionService::class);
        $this->container->bind(PositionPermissionService::class);
        $this->container->bind(PermissionController::class);
        $this->container->bind(PositionPermissionController::class);
        $this->container->bind(OrganizationScopeService::class);
        $this->container->bind(AuthorizationService::class);
        $this->container->bind(AuthorizationMiddleware::class);

        $this->container->bind(AuthTokenRepository::class);
        $this->container->bind(AuthenticationMiddleware::class);
        $this->container->bind(AuthenticationController::class);
        $this->container->bind(AuthContextController::class);
        $this->container->bind(
            AuthenticationService::class,
            fn (): AuthenticationService => new AuthenticationService(
                $this->container->make(UserRepository::class),
                $this->container->make(AuthTokenRepository::class),
                $this->config
            )
        );

        $this->container->singleton(Router::class);

        $this->container->singleton(ExceptionHandler::class);
    }
}
