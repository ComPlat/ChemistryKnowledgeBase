<div id="ce-side-panel-content" style="">
    <div id="ce-topic-switch" class="ce-page-type ce-page-type-topic" style="float:left; cursor: pointer;">Topics</div>
    @if($title->getNamespace() === NS_CATEGORY)
    <div id="ce-publication-switch" class="ce-page-type ce-page-type-publication" style="float:left; cursor: pointer;">Publications</div>
    @endif
    @if($title->getNamespace() === NS_MAIN && !$title->isSubpage())
    <div id="ce-investigation-switch" class="ce-page-type ce-page-type-investigation" style="float:left; cursor: pointer;">Investigations</div>
    @endif
    <div style="clear: both;" id="ce-topic-content" class="ce-content-panel">
        <h3>Current site</h3>
        {!! $categories !!}

        <h3>Sub-topics</h3>
        {!! $categoryTree !!}
    </div>
    <div style="clear: both; display:none;" id="ce-publication-content" class="ce-content-panel">
        <h3>Publications</h3>
        {!! $publicationList !!}
    </div>
    <div style="clear: both; display:none;" id="ce-investigation-content" class="ce-content-panel">
        <h3>Investigations</h3>
        {!! $investigationList !!}

    </div>
</div>
