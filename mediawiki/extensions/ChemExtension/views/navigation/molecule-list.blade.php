<div id="ce-molecules-list">
<ul>
    @foreach($moleculesList as $l)
        <li><a href="{{$l['page']->getFullURL()}}">{{$l['name']}}</a></li>
    @endforeach
</ul>
<div id="ce-moleculelist-search-hint"></div>
</div>