<?php

namespace Netgen\Bundle\ContentBrowserBundle\Controller\API;

use Netgen\Bundle\ContentBrowserBundle\Exceptions\NotFoundException;
use Netgen\Bundle\ContentBrowserBundle\Repository\RepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller as BaseController;
use Netgen\Bundle\ContentBrowserBundle\Repository\Location;
use Symfony\Component\HttpFoundation\JsonResponse;
use DateTime;

class TreeController extends BaseController
{
    /**
     * @var \Netgen\Bundle\ContentBrowserBundle\Repository\RepositoryInterface
     */
    protected $repository;

    /**
     * Returns tree config.
     *
     * @param string $tree
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getConfig($tree)
    {
        $translator = $this->get('translator');
        $this->initRepository($tree);

        $rootLocations = array();
        foreach ($this->repository->getRootLocations() as $location) {
            $locationData = $this->serializeLocation($location);
            $locationData['has_children'] = $this->repository->hasChildrenCategories($location);
            $rootLocations[] = $locationData;
        }

        $config = $this->repository->getConfig();
        $data = array(
            'name' => $translator->trans('netgen_content_browser.trees.' . $tree . '.name'),
            'root_locations' => $rootLocations,
            'min_selected' => $config['min_selected'],
            'max_selected' => $config['max_selected'],
            'default_columns' => $config['default_columns'],
            'available_columns' => $this->repository->getAvailableColumns(),
        );

        return new JsonResponse($data);
    }

    /**
     * Loads all children of the specified location.
     *
     * @param string $tree
     * @param int|string $locationId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getLocationChildren($tree, $locationId)
    {
        $this->initRepository($tree);

        $location = $this->repository->getLocation($locationId);
        $children = $this->repository->getChildren($location);

        $childrenData = array();
        foreach ($children as $child) {
            $childrenData[] = $this->serializeLocation(
                $child,
                $this->repository->hasChildren($child)
            );
        }

        $data = array(
            'path' => $this->getLocationPath($location),
            'children' => $childrenData,
        );

        return new JsonResponse($data);
    }

    /**
     * Loads all children of the specified location.
     *
     * @param string $tree
     * @param int|string $locationId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getLocationCategories($tree, $locationId)
    {
        $this->initRepository($tree);

        $location = $this->repository->getLocation($locationId);
        $children = $this->repository->getCategories($location);

        $childrenData = array();
        foreach ($children as $child) {
            $childrenData[] = $this->serializeLocation(
                $child,
                $this->repository->hasChildrenCategories($child)
            );
        }

        $data = array(
            'path' => $this->getLocationPath($location),
            'children' => $childrenData,
        );

        return new JsonResponse($data);
    }

    /**
     * Generates the location path.
     *
     * @param \Netgen\Bundle\ContentBrowserBundle\Repository\Location $location
     *
     * @return array
     */
    protected function getLocationPath(Location $location)
    {
        $path = array();
        foreach ($location->path as $pathLocationId) {
            $pathItemLocation = $this->repository->getLocation($pathLocationId);
            if (!$this->repository->isInsideRootLocations($pathItemLocation)) {
                continue;
            }

            $path[] = array(
                'id' => $pathItemLocation->id,
                'name' => $pathItemLocation->name,
            );
        }

        return $path;
    }

    /**
     * Serializes the location.
     *
     * @param \Netgen\Bundle\ContentBrowserBundle\Repository\Location $location
     * @param bool $hasChildren
     *
     * @return array
     */
    protected function serializeLocation(Location $location, $hasChildren = false)
    {
        return array(
            'id' => $location->id,
            'parent_id' => !$this->repository->isRootLocation($location) ?
                $location->parentId :
                null,
            'name' => $location->name,
            'enabled' => $location->isEnabled,
            'has_children' => (bool)$hasChildren,
            'html' => $this->renderView(
                $this->repository->getConfig()['location_template'],
                array(
                    'location' => $location,
                )
            ),
        ) + $location->additionalColumns;
    }

    /**
     * Builds the repository from provided tree config.
     *
     * @param string $tree
     *
     * @throws \Netgen\Bundle\ContentBrowserBundle\Exceptions\NotFoundException If tree config does not exist
     *
     * @return \Netgen\Bundle\ContentBrowserBundle\Repository\RepositoryInterface
     */
    protected function initRepository($tree)
    {
        if ($this->repository instanceof RepositoryInterface) {
            return;
        }

        $trees = $this->getParameter('netgen_content_browser.trees');

        if (!isset($trees[$tree])) {
            throw new NotFoundException("Tree {$tree} not found.");
        }

        $this->repository = $this->get('netgen_content_browser.repository');
        $this->repository->setConfig($trees[$tree]);
    }
}
