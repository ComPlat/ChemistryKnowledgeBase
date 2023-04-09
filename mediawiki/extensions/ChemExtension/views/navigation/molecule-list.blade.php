<div id="ce-molecules-list" class="ce-list">
<ul>
    @foreach($moleculesList as $l)
        <li><a href="{{$l['page']->getFullURL()}}">{{$l['name']}}</a></li>
    @endforeach
</ul>
<div id="ce-moleculelist-search-hint"></div>
</div>