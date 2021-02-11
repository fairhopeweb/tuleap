<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

use Tuleap\AgileDashboard\BlockScrumAccess;
use Tuleap\AgileDashboard\Planning\ConfigurationCheckDelegation;
use Tuleap\AgileDashboard\Planning\PlanningAdministrationDelegation;
use Tuleap\AgileDashboard\Planning\RootPlanning\RootPlanningEditionEvent;
use Tuleap\AgileDashboard\REST\v1\Milestone\OriginalProjectCollector;
use Tuleap\Layout\ServiceUrlCollector;
use Tuleap\ProgramManagement\Adapter\Program\Plan\CanPrioritizeFeaturesDAO;
use Tuleap\ProgramManagement\Adapter\ProjectAdmin\PermissionPerGroupSectionBuilder;
use Tuleap\ProgramManagement\Adapter\Workspace\WorkspaceDAO;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupPaneCollector;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupFormatter;
use Tuleap\Queue\QueueFactory;
use Tuleap\Queue\WorkerEvent;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation\CreateProgramIncrementsRunner;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation\PendingArtifactCreationDao;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation\TaskBuilder;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\CreationCheck\RequiredFieldChecker;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\CreationCheck\SemanticChecker;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\CreationCheck\StatusSemanticChecker;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\CreationCheck\WorkflowChecker;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\ArtifactLinkFieldAdapter;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\DescriptionFieldAdapter;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\ProgramIncrementTrackerConfigurationBuilder;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\ReplicationDataAdapter;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\NatureAnalyzerException;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\SourceArtifactNatureAnalyzer;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\StatusFieldAdapter;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\SynchronizedFieldsAdapter;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\TimeFrameFieldsAdapter;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\TitleFieldAdapter;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\TopBacklog\ArtifactsExplicitTopBacklogDAO;
use Tuleap\ProgramManagement\Adapter\Program\Hierarchy\ProgramManagementHierarchyDAO;
use Tuleap\ProgramManagement\Adapter\Program\Plan\PlanDao;
use Tuleap\ProgramManagement\Adapter\Program\Plan\PlanProgramAdapter;
use Tuleap\ProgramManagement\Adapter\Program\Plan\PlanProgramIncrementConfigurationBuilder;
use Tuleap\ProgramManagement\Adapter\Program\Plan\PlanTrackerException;
use Tuleap\ProgramManagement\Adapter\Program\Plan\ProgramAdapter;
use Tuleap\ProgramManagement\Adapter\Program\PlanningAdapter;
use Tuleap\ProgramManagement\Adapter\Program\ProgramDao;
use Tuleap\ProgramManagement\Adapter\Program\Tracker\ProgramTrackerException;
use Tuleap\ProgramManagement\Adapter\ProjectAdapter;
use Tuleap\ProgramManagement\Adapter\Team\TeamDao;
use Tuleap\ProgramManagement\DisplayProgramBacklogController;
use Tuleap\ProgramManagement\EventRedirectAfterArtifactCreationOrUpdateHandler;
use Tuleap\ProgramManagement\Program\Backlog\AsynchronousCreation\ArtifactCreatedHandler;
use Tuleap\ProgramManagement\Program\Backlog\CreationCheck\ArtifactCreatorChecker;
use Tuleap\ProgramManagement\Program\Backlog\CreationCheck\ProgramIncrementArtifactCreatorChecker;
use Tuleap\ProgramManagement\Program\Backlog\Plan\ConfigurationChecker;
use Tuleap\ProgramManagement\Program\Backlog\Plan\PlanCheckException;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\ProgramIncrementArtifactLinkType;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldFromProgramAndTeamTrackersCollectionBuilder;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollectionBuilder;
use Tuleap\ProgramManagement\Program\Backlog\TrackerCollectionFactory;
use Tuleap\ProgramManagement\RedirectParameterInjector;
use Tuleap\ProgramManagement\REST\ResourcesInjector;
use Tuleap\ProgramManagement\ProgramService;
use Tuleap\ProgramManagement\ProgramTracker;
use Tuleap\ProgramManagement\Team\RootPlanning\RootPlanningEditionHandler;
use Tuleap\ProgramManagement\Workspace\ComponentInvolvedVerifier;
use Tuleap\Tracker\Artifact\CanSubmitNewArtifact;
use Tuleap\Tracker\Artifact\Event\ArtifactCreated;
use Tuleap\Tracker\Artifact\Event\ArtifactDeleted;
use Tuleap\Tracker\Artifact\Event\ArtifactUpdated;
use Tuleap\Tracker\Artifact\RedirectAfterArtifactCreationOrUpdateEvent;
use Tuleap\Tracker\Artifact\Renderer\BuildArtifactFormActionEvent;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenterFactory;
use Tuleap\Tracker\Hierarchy\TrackerHierarchyDelegation;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../../tracker/include/trackerPlugin.php';
require_once __DIR__ . '/../../cardwall/include/cardwallPlugin.php';
require_once __DIR__ . '/../../agiledashboard/include/agiledashboardPlugin.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class program_managementPlugin extends Plugin
{
    public const SERVICE_SHORTNAME = 'plugin_program_management';

    public function __construct(?int $id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_SYSTEM);
        bindtextdomain('tuleap-program_management', __DIR__ . '/../site-content');
    }

    public function getHooksAndCallbacks(): Collection
    {
        $this->addHook(RootPlanningEditionEvent::NAME);
        $this->addHook(NaturePresenterFactory::EVENT_GET_ARTIFACTLINK_NATURES, 'getArtifactLinkNatures');
        $this->addHook(NaturePresenterFactory::EVENT_GET_NATURE_PRESENTER, 'getNaturePresenter');
        $this->addHook(Tracker_Artifact_XMLImport_XMLImportFieldStrategyArtifactLink::TRACKER_ADD_SYSTEM_NATURES, 'trackerAddSystemNatures');
        $this->addHook(CanSubmitNewArtifact::NAME);
        $this->addHook(ArtifactCreated::NAME);
        $this->addHook(ArtifactUpdated::NAME);
        $this->addHook(ArtifactDeleted::NAME);
        $this->addHook(WorkerEvent::NAME);
        $this->addHook(Event::REST_RESOURCES);
        $this->addHook(PlanningAdministrationDelegation::NAME);
        $this->addHook('tracker_usage', 'trackerUsage');
        $this->addHook('project_is_deleted', 'projectIsDeleted');
        $this->addHook(ConfigurationCheckDelegation::NAME);
        $this->addHook(BlockScrumAccess::NAME);
        $this->addHook(TrackerHierarchyDelegation::NAME);
        $this->addHook(OriginalProjectCollector::NAME);
        $this->addHook(Event::SERVICE_CLASSNAMES);
        $this->addHook(Event::SERVICES_ALLOWED_FOR_PROJECT);
        $this->addHook(ServiceUrlCollector::NAME);
        $this->addHook(CollectRoutesEvent::NAME);
        $this->addHook(RedirectAfterArtifactCreationOrUpdateEvent::NAME);
        $this->addHook(BuildArtifactFormActionEvent::NAME);
        $this->addHook(PermissionPerGroupPaneCollector::NAME);

        return parent::getHooksAndCallbacks();
    }

    public function getDependencies(): array
    {
        return ['tracker', 'agiledashboard', 'cardwall'];
    }

    public function service_classnames(array &$params): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        $params['classnames'][$this->getServiceShortname()] = ProgramService::class;
    }

    public function getServiceShortname(): string
    {
        return self::SERVICE_SHORTNAME;
    }

    public function serviceUrlCollector(ServiceUrlCollector $collector): void
    {
        if ($collector->getServiceShortname() === $this->getServiceShortname()) {
            $collector->setUrl(
                sprintf('/program_management/%s', urlencode($collector->getProject()->getUnixNameLowerCase()))
            );
        }
    }

    public function getPluginInfo(): PluginInfo
    {
        if ($this->pluginInfo === null) {
            $pluginInfo = new PluginInfo($this);
            $pluginInfo->setPluginDescriptor(
                new PluginDescriptor(
                    dgettext('tuleap-program_management', 'Program Management'),
                    '',
                    dgettext('tuleap-program_management', 'Enables managing several related projects, synchronizing teams and milestones')
                )
            );
            $this->pluginInfo = $pluginInfo;
        }
        return $this->pluginInfo;
    }

    public function collectRoutesEvent(CollectRoutesEvent $event): void
    {
        $event->getRouteCollector()->addGroup(
            '/program_management',
            function (FastRoute\RouteCollector $r) {
                $r->get('/{project_name:[A-z0-9-]+}[/]', $this->getRouteHandler('routeGetProgramManagement'));
            }
        );
    }

    public function routeGetProgramManagement(): DisplayProgramBacklogController
    {
        return new DisplayProgramBacklogController(
            ProjectManager::instance(),
            new \Tuleap\Project\Flags\ProjectFlagsBuilder(new \Tuleap\Project\Flags\ProjectFlagsDao()),
            new ProgramAdapter(
                ProjectManager::instance(),
                new ProgramDao()
            ),
            TemplateRendererFactory::build()->getRenderer(__DIR__ . "/../templates"),
            new ProgramIncrementTrackerConfigurationBuilder(
                $this->getPlanConfigurationBuilder()
            ),
        );
    }

    public function rootPlanningEditionEvent(RootPlanningEditionEvent $event): void
    {
        $handler = new RootPlanningEditionHandler(new TeamDao());
        $handler->handle($event);
    }

    /**
     * @see NaturePresenterFactory::EVENT_GET_ARTIFACTLINK_NATURES
     */
    public function getArtifactLinkNatures(array $params): void
    {
        $params['natures'][] = new ProgramIncrementArtifactLinkType();
    }

    /**
     * @see NaturePresenterFactory::EVENT_GET_NATURE_PRESENTER
     */
    public function getNaturePresenter(array $params): void
    {
        if ($params['shortname'] === ProgramIncrementArtifactLinkType::ART_LINK_SHORT_NAME) {
            $params['presenter'] = new ProgramIncrementArtifactLinkType();
        }
    }

    /**
     * @see Tracker_Artifact_XMLImport_XMLImportFieldStrategyArtifactLink::TRACKER_ADD_SYSTEM_NATURES
     */
    public function trackerAddSystemNatures(array $params): void
    {
        $params['natures'][] = ProgramIncrementArtifactLinkType::ART_LINK_SHORT_NAME;
    }

    public function canSubmitNewArtifact(CanSubmitNewArtifact $can_submit_new_artifact): void
    {
        $artifact_creator_checker = new ArtifactCreatorChecker(
            $this->getProjectIncrementCreatorChecker(),
            $this->getPlanConfigurationBuilder()
        );

        $tracker_data = new ProgramTracker($can_submit_new_artifact->getTracker());
        $project_data = ProjectAdapter::build($can_submit_new_artifact->getTracker()->getProject());
        if (
            ! $artifact_creator_checker->canCreateAnArtifact(
                $can_submit_new_artifact->getUser(),
                $tracker_data,
                $project_data
            )
        ) {
            $can_submit_new_artifact->disableArtifactSubmission();
        }
    }

    public function workerEvent(WorkerEvent $event): void
    {
        $create_mirrors_runner = $this->getProgramIncrementRunner();
        $create_mirrors_runner->addListener($event);
    }

    public function trackerArtifactCreated(ArtifactCreated $event): void
    {
        $program_dao = new ProgramDao();
        $logger      = $this->getLogger();

        $artifact = $event->getArtifact();

        $logger->debug(
            sprintf(
                "Store program create with #%d by user #%d",
                $artifact->getId(),
                (int) $event->getUser()->getId()
            )
        );

        $this->cleanUpFromTopBacklogFeatureAddedToAProgramIncrement($artifact);

        $handler = new ArtifactCreatedHandler(
            $program_dao,
            $this->getProgramIncrementRunner(),
            new PendingArtifactCreationDao(),
            $this->getPlanConfigurationBuilder()
        );
        $handler->handle($event);
    }

    public function trackerArtifactUpdated(ArtifactUpdated $event): void
    {
        $this->cleanUpFromTopBacklogFeatureAddedToAProgramIncrement($event->getArtifact());
    }

    private function cleanUpFromTopBacklogFeatureAddedToAProgramIncrement(\Tuleap\Tracker\Artifact\Artifact $artifact): void
    {
        (new ArtifactsExplicitTopBacklogDAO())->removeArtifactsPlannedInAProgramIncrement($artifact->getId());
    }

    public function trackerArtifactDeleted(ArtifactDeleted $artifact_deleted): void
    {
        (new ArtifactsExplicitTopBacklogDAO())->removeArtifactFromExplicitTopBacklog($artifact_deleted->getArtifact()->getID());
    }

    /**
     * @see         Event::REST_RESOURCES
     *
     * @psalm-param array{restler: \Luracast\Restler\Restler} $params
     */
    public function restResources(array $params): void
    {
        $injector = new ResourcesInjector();
        $injector->populate($params['restler']);
    }

    public function planningAdministrationDelegation(
        PlanningAdministrationDelegation $planning_administration_delegation
    ): void {
        $component_involved_verifier = new ComponentInvolvedVerifier(
            new TeamDao(),
            new ProgramDao()
        );
        $project_data                = ProjectAdapter::build($planning_administration_delegation->getProject());
        if ($component_involved_verifier->isInvolvedInAProgramWorkspace($project_data)) {
            $planning_administration_delegation->enablePlanningAdministrationDelegation();
        }
    }

    public function trackerHierarchyDelegation(
        TrackerHierarchyDelegation $tracker_hierarchy_delegation
    ): void {
        if ((new ProgramManagementHierarchyDAO())->isPartOfAHierarchy(new ProgramTracker($tracker_hierarchy_delegation->getTracker()))) {
            $tracker_hierarchy_delegation->enableTrackerHierarchyDelegation($this->getPluginInfo()->getPluginDescriptor()->getFullName());
        }
    }

    public function trackerUsage(array $params): void
    {
        if ((new PlanDao())->isPartOfAPlan(new ProgramTracker($params['tracker']))) {
            $params['result'] = [
                'can_be_deleted' => false,
                'message'        => $this->getPluginInfo()->getPluginDescriptor()->getFullName()
            ];
        }
    }

    public function projectIsDeleted(): void
    {
        (new WorkspaceDAO())->dropUnusedComponents();
    }

    public function externalParentCollector(OriginalProjectCollector $original_project_collector): void
    {
        $source_analyser = new SourceArtifactNatureAnalyzer(new TeamDao(), ProjectManager::instance(), Tracker_ArtifactFactory::instance());
        $artifact        = $original_project_collector->getOriginalArtifact();
        $user            = $original_project_collector->getUser();

        try {
            $project = $source_analyser->retrieveProjectOfMirroredArtifact($artifact, $user);
            if (! $project) {
                return;
            }

            $original_project_collector->setOriginalProject($project);
        } catch (NatureAnalyzerException $exception) {
            $logger = $this->getLogger();
            $logger->debug($exception->getMessage(), ['exception' => $exception]);
        }
    }

    public function configurationCheckDelegation(ConfigurationCheckDelegation $configuration_check_delegation): void
    {
        $plan_program_builder = new PlanProgramAdapter(
            ProjectManager::instance(),
            new URLVerification(),
            new TeamDao()
        );

        $configuration_checker = new ConfigurationChecker(
            $plan_program_builder,
            $this->getPlanConfigurationBuilder()
        );
        try {
            $configuration_checker->getProgramIncrementTracker(
                $configuration_check_delegation->getUser(),
                $configuration_check_delegation->getProject()
            );
        } catch (PlanTrackerException | ProgramTrackerException | PlanCheckException $e) {
            $configuration_check_delegation->disablePlanning();
            $this->getLogger()->debug($e->getMessage());
        }
    }

    public function blockScrumAccess(BlockScrumAccess $block_scrum_access): void
    {
        $program_store = new ProgramDao();
        if ($program_store->isProjectAProgramProject((int) $block_scrum_access->getProject()->getID())) {
            $block_scrum_access->disableScrumAccess();
        }
    }

    public function redirectAfterArtifactCreationOrUpdateEvent(RedirectAfterArtifactCreationOrUpdateEvent $event): void
    {
        $processor = new EventRedirectAfterArtifactCreationOrUpdateHandler();
        $processor->process($event->getRequest(), $event->getRedirect(), $event->getArtifact());
    }

    public function buildArtifactFormActionEvent(BuildArtifactFormActionEvent $event): void
    {
        $redirect_in_service = $event->getRequest()->get('program_increment') && $event->getRequest()->get('program_increment') === "create";
        if (! $redirect_in_service) {
            return;
        }

        $redirect = new RedirectParameterInjector();
        $redirect->injectAndInformUserAboutProgramItem($event->getRedirect(), $GLOBALS['Response']);
    }

    private function getProjectIncrementCreatorChecker(): ProgramIncrementArtifactCreatorChecker
    {
        $form_element_factory    = \Tracker_FormElementFactory::instance();
        $timeframe_dao           = new \Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeDao();
        $semantic_status_factory = new Tracker_Semantic_StatusFactory();
        $logger                  = $this->getLogger();

        return new ProgramIncrementArtifactCreatorChecker(
            $this->getTeamProjectCollectionBuilder(),
            new TrackerCollectionFactory(
                $this->getPlanningAdapter()
            ),
            new SynchronizedFieldFromProgramAndTeamTrackersCollectionBuilder(
                new SynchronizedFieldsAdapter(
                    new ArtifactLinkFieldAdapter($form_element_factory),
                    new TitleFieldAdapter(new Tracker_Semantic_TitleFactory()),
                    new DescriptionFieldAdapter(new Tracker_Semantic_DescriptionFactory()),
                    new StatusFieldAdapter($semantic_status_factory),
                    new TimeFrameFieldsAdapter(new SemanticTimeframeBuilder($timeframe_dao, $form_element_factory))
                )
            ),
            new SemanticChecker(
                new \Tracker_Semantic_TitleDao(),
                new \Tracker_Semantic_DescriptionDao(),
                $timeframe_dao,
                new StatusSemanticChecker(new Tracker_Semantic_StatusDao(), $semantic_status_factory),
            ),
            new RequiredFieldChecker($logger),
            new WorkflowChecker(
                new Workflow_Dao(),
                new Tracker_Rule_Date_Dao(),
                new Tracker_Rule_List_Dao(),
                $logger
            ),
            $logger
        );
    }

    private function getLogger(): \Psr\Log\LoggerInterface
    {
        return BackendLogger::getDefaultLogger("program_management_syslog");
    }

    private function getPlanningAdapter(): PlanningAdapter
    {
        return new PlanningAdapter(\PlanningFactory::build());
    }

    private function getTeamProjectCollectionBuilder(): TeamProjectsCollectionBuilder
    {
        return new TeamProjectsCollectionBuilder(
            new ProgramDao(),
            $this->getProjectDataAdapter()
        );
    }

    private function getProjectDataAdapter(): ProjectAdapter
    {
        return new ProjectAdapter(ProjectManager::instance());
    }

    private function getProgramIncrementRunner(): CreateProgramIncrementsRunner
    {
        $logger = $this->getLogger();

        return new CreateProgramIncrementsRunner(
            $this->getLogger(),
            new QueueFactory($logger),
            new ReplicationDataAdapter(
                Tracker_ArtifactFactory::instance(),
                UserManager::instance(),
                new PendingArtifactCreationDao(),
                Tracker_Artifact_ChangesetFactoryBuilder::build()
            ),
            new TaskBuilder()
        );
    }

    private function getPlanConfigurationBuilder(): PlanProgramIncrementConfigurationBuilder
    {
        return new PlanProgramIncrementConfigurationBuilder(
            new PlanDao(),
            TrackerFactory::instance()
        );
    }

    public function permissionPerGroupPaneCollector(PermissionPerGroupPaneCollector $event): void
    {
        $ugroup_manager                       = new UGroupManager();
        $permission_per_group_section_builder = new PermissionPerGroupSectionBuilder(
            new CanPrioritizeFeaturesDAO(),
            new PermissionPerGroupUGroupFormatter($ugroup_manager),
            $ugroup_manager,
            TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../templates')
        );

        $permission_per_group_section_builder->collectSections($event);
    }
}