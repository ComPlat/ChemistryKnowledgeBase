<div id="ce-side-panel-content" style="">
    <div class="ce-side-panel-footer">
        <span id="ce-side-panel-expand-button"><img src="{{$imgPath}}/expand.png"></span>
    </div>
    <div>
        <div id="ce-topic-element" class="ce-tree-element {{$type=='topic'?'ce-page-type-topic':'ce-tree-element-grey'}}">topic</div>
        <div>
        <div id="ce-tree-element-link" style=" margin-left: 15px;"></div>
        <div id="ce-publication-element" class="ce-tree-element {{$type=='publication'?'ce-page-type-publication':'ce-tree-element-grey'}}">publication</div>
        </div>
        <div>
            <div id="ce-tree-element-link" style="margin-left: 115px; top: -5px"></div>
            <div id="ce-investigation-element" class="ce-tree-element {{$type=='investigation'?'ce-page-type-investigation':'ce-tree-element-grey'}}">investigation</div>
        </div>
        <div>
            <div id="ce-tree-element-link" style="margin-left: 230px; top: -10px"></div>
            <div id="ce-molecule-element" class="ce-tree-element {{$type=='molecule'?'ce-page-type-molecule':'ce-tree-element-grey'}}">molecule</div>
        </div>
    </div>

    <div class="ce-breadcrumb">
        <h3>Current site</h3>
        {!! $categories !!}
    </div>

    <div id="ce-topic-content" class="ce-content-panel">
        <h3>Topics</h3>
        <div class="ce-content">
        {!! $categoryTree !!}
        </div>
    </div>

    <div style="display:none;" id="ce-publication-filter" class="ce-filter-panel">
        {!! $publicationFilter !!}
    </div>
    <div style="display:none;" id="ce-publication-content" class="ce-content-panel">
        <div class="ce-content">
        {!! $publicationList !!}
        </div>
    </div>
    <div style="display:none;" id="ce-investigation-content" class="ce-content-panel">
        <h3>Investigations</h3>
        {!! $investigationList !!}

    </div>
    <div style="display:none;" id="ce-molecules-filter" class="ce-filter-panel">
        {!! $moleculesFilter !!}
    </div>
    <div style="display:none;" id="ce-molecules-content" class="ce-content-panel">
        {!! $moleculesList !!}

    </div>

</div>

