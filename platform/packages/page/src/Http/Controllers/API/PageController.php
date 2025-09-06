<?php

namespace Botble\Page\Http\Controllers\API;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Page\Http\Resources\ListPageResource;
use Botble\Page\Http\Resources\PageResource;
use Botble\Page\Models\Page;
use Illuminate\Http\Request;

class PageController extends BaseController
{
    /**
     * List pages
     *
     * @group Page
     */
    public function index(Request $request)
    {
        $pages = Page::query()
            ->wherePublished()
            ->with('slugable')
            ->paginate($request->integer('per_page', 10) ?: 10);

        return $this
            ->httpResponse()
            ->setData(ListPageResource::collection($pages))
            ->toApiResponse();
    }

    /**
     * Get page by ID
     *
     * @group Page
     * @queryParam id Find by ID of page.
     */
    public function show(int|string $id)
    {
        $page = Page::query()
            ->where('id', $id)
            ->wherePublished()
            ->with('slugable')
            ->first();

        if (! $page) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage(__('Not found'));
        }

        return $this
            ->httpResponse()
            ->setData(new PageResource($page))
            ->toApiResponse();
    }
}
