<?php

// Run with wp eval-file. Requests and query changes are process-local.
$extension = new MunicipioEventExtensions\Archive();
$request = new WP_REST_Request('GET', '/municipio/v1/posts-list/render');
$request->set_param('attributes', wp_json_encode(['postType' => 'event', 'queryVarsPrefix' => 'archive_']));
$clauses = ['fields' => 'wp_posts.*', 'join' => '', 'where' => '', 'groupby' => '', 'orderby' => 'post_date DESC'];
$eventQuery = new WP_Query();
$eventQuery->set('post_type', ['event']);
$postQuery = new WP_Query();
$postQuery->set('post_type', ['post']);
$check = static function (bool $condition): void {
    if (!$condition) {
        throw new RuntimeException('Archive request isolation failed.');
    }
};

$check($extension->filterQuery($clauses, $eventQuery) === $clauses);
$extension->beginRender(null, [], $request);
$check($extension->filterQuery($clauses, $eventQuery) !== $clauses);
$check($extension->filterQuery($clauses, $postQuery) === $clauses);

// Nested REST dispatch must not inherit the archive SQL or clear its caller.
$other = new WP_REST_Request('GET', '/wp/v2/posts');
$extension->beginRender(null, [], $other);
$check($extension->filterQuery($clauses, $eventQuery) === $clauses);
$extension->endRender(null, [], $other);
$check($extension->filterQuery($clauses, $eventQuery) !== $clauses);
$extension->endRender(null, [], $request);
$check($extension->filterQuery($clauses, $eventQuery) === $clauses);

$request->set_param('attributes', wp_json_encode(['postType' => 'event', 'queryVarsPrefix' => 'block_']));
$extension->beginRender(null, [], $request);
$check($extension->filterQuery($clauses, $eventQuery) === $clauses);
$extension->endRender(null, [], $request);
echo "Archive SQL is isolated to the event archive REST callback.\n";
