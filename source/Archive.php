<?php

declare(strict_types=1);

namespace MunicipioEventExtensions;

use EventManagerIntegration\EventArchive;
use EventManagerIntegration\EventPostObject;
use Municipio\PostObject\PostObjectInterface;

final class Archive
{
    private ?EventArchive $archive = null;
    private array $contexts = [];

    public function register(): void
    {
        add_filter('rest_request_before_callbacks', [$this, 'beginRender'], 10, 3);
        add_filter('rest_request_after_callbacks', [$this, 'endRender'], 10, 3);
        add_filter('posts_clauses', [$this, 'filterQuery'], 10, 2);
        add_filter('Municipio/DecoratePostObject', [$this, 'decorateDate'], 20);
    }

    public function beginRender(mixed $response, array $handler, \WP_REST_Request $request): mixed
    {
        $this->contexts[] = $this->archive;
        $this->archive = null;
        if ($request->get_route() !== '/municipio/v1/posts-list/render') {
            return $response;
        }

        $attributes = json_decode((string) $request->get_param('attributes'), true);
        if (($attributes['postType'] ?? null) !== 'event' || ($attributes['queryVarsPrefix'] ?? null) !== 'archive_') {
            return $response;
        }

        $this->archive = ArchiveIntegration::enabled();
        return $response;
    }

    public function endRender(mixed $response, array $handler, \WP_REST_Request $request): mixed
    {
        $this->archive = array_pop($this->contexts);
        return $response;
    }

    public function filterQuery(array $clauses, \WP_Query $query): array
    {
        if ($this->archive === null || (array) $query->get('post_type') !== ['event']) {
            return $clauses;
        }

        $clauses['fields'] = $this->archive->eventFilterSelect($clauses['fields']);
        $clauses['join'] = $this->archive->eventFilterJoin($clauses['join']);
        if (($query->query['date_query'] ?? null) !== null) {
            $clauses['where'] = $this->archive->eventFilterWhere($clauses['where'], $query);
        }
        $clauses['groupby'] = $this->archive->eventFilterGroupBy($clauses['groupby']);
        $clauses['orderby'] = $this->archive->eventFilterOrderBy($clauses['orderby']);
        return $clauses;
    }

    public function decorateDate(PostObjectInterface $post): PostObjectInterface
    {
        if ($this->archive === null || $post->getPostType() !== 'event' || ($post->startDate ?? null) === null) {
            return $post;
        }

        return EventPostObject::create($post, strtotime($post->startDate));
    }
}
