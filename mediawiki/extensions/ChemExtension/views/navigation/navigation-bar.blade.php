<div id="ce-side-panel-content" style="">
    <div class="ce-side-panel-footer">
        <span id="ce-side-panel-expand-button"><img src="{{$imgPath}}/expand.png"></span>
    </div>
    <div id="ce-topic-switch" class="ce-page-type ce-page-type-topic" style="float:left; cursor: pointer;">Topics</div>
    @if($showPublications)
    <div id="ce-publication-switch" class="ce-page-type ce-page-type-publication" style="float:left; cursor: pointer;">Publications</div>
    @endif
    @if($showInvestigations)
    <div id="ce-investigation-switch" class="ce-page-type ce-page-type-investigation" style="float:left; cursor: pointer;">Investigations</div>
    @endif
    <div id="ce-molecules-switch" class="ce-page-type ce-page-type-molecules" style="float:left; cursor: pointer;">Molecules</div>
    <div style="clear: both;" id="ce-topic-content" class="ce-content-panel">
        <h3>Current site</h3>
        <div class="ce-breadcrumb">
        {!! $categories !!}
        </div>
        <h3>Topics</h3>
        <div class="ce-content">
        {!! $categoryTree !!}
        </div>
    </div>
    <div style="clear: both; display:none;" id="ce-publication-content" class="ce-content-panel">
        <h3>Current site</h3>
        <div class="ce-breadcrumb">
        {!! $categories !!}
        </div>
        <h3>Publications</h3>
        <div class="ce-content">
        {!! $publicationList !!}
        </div>
    </div>
    <div style="clear: both; display:none;" id="ce-investigation-content" class="ce-content-panel">
        <h3>Investigations</h3>
        {!! $investigationList !!}

    </div>
    <div style="clear: both; display:none;" id="ce-molecules-content" class="ce-content-panel">
        <h3>Molecules</h3>
        {!! $moleculesList !!}

    </div>

</div>

