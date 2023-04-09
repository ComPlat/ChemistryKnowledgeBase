<div id="ce-side-panel-content" style="">
    <div class="ce-side-panel-footer">
        <span id="ce-side-panel-expand-button"><img src="{{$imgPath}}/expand.png"></span>
    </div>
    @include('navigation.schema-graph', [ 'type' => $type])

    <div class="ce-breadcrumb">
        <h3>Current site</h3>
        {!! $categories !!}
    </div>

    <!-- Topic tab -->
    <div id="ce-topic-content" class="ce-content-panel">
        <h3>Topics</h3>
        <div class="ce-content">
        {!! $categoryTree !!}
        </div>
    </div>

    <!-- Publication tab -->
    <div style="display:none;" id="ce-publication-filter" class="ce-filter-panel">
        {!! $publicationFilter !!}
    </div>
    <div style="display:none;" id="ce-publication-content" class="ce-content-panel">
        <div class="ce-content">
        @include('navigation.publication-list', ['list' => $publicationList])
        </div>
    </div>

    <!-- Investigation tab -->
    <div style="display:none;" id="ce-investigation-filter" class="ce-filter-panel">
        {!! $investigationFilter !!}
    </div>
    <div style="display:none;" id="ce-investigation-content" class="ce-content-panel">
        @include('navigation.investigation-list', ['list' => $investigationList, 'type' => $type])
    </div>

    <!-- Molecules tab -->
    <div style="display:none;" id="ce-molecules-filter" class="ce-filter-panel">
        {!! $moleculesFilter !!}
    </div>
    <div style="display:none;" id="ce-molecules-content" class="ce-content-panel">
        @include('navigation.molecule-list', ['moleculesList' => [] ])
    </div>

</div>

