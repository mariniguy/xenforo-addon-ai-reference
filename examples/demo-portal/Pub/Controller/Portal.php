<?php

namespace Demo\Portal\Pub\Controller;

use XF\Mvc\ParameterBag;
use XF\Pub\Controller\AbstractController;

class Portal extends AbstractController
{
    /**
     * GET /portal
     * Main portal page — lists all featured threads.
     */
    public function actionIndex(ParameterBag $params): \XF\Mvc\Reply\AbstractReply
    {
        /** @var \Demo\Portal\Repository\FeaturedThread $featuredThreadRepo */
        $featuredThreadRepo = $this->repository('Demo\Portal:FeaturedThread');

        $finder = $featuredThreadRepo->findFeaturedThreadsForPortal();

        $page    = $this->filterPage();
        $perPage = $this->options()->demoPortalThreadsPerPage ?? 10;

        $finder->limitByPage($page, $perPage);

        $featuredThreads = $finder->fetch();
        $totalThreads    = $finder->total();

        $this->assertValidPage($page, $perPage, $totalThreads, 'portal');

        $viewParams = [
            'featuredThreads' => $featuredThreads,
            'page'            => $page,
            'perPage'         => $perPage,
            'total'           => $totalThreads,
        ];

        return $this->view('Demo\Portal:Portal\Index', 'demo_portal_view', $viewParams);
    }

    /**
     * Ensure the user can access this controller.
     * Called automatically before every action.
     */
    protected function preDispatchController(string $action, ParameterBag $params): void
    {
        // Require the user to be logged in for POST actions that modify data.
        // Read-only portal is visible to guests.
    }
}
