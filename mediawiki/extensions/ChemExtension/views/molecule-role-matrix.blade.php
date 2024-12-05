<h2>Molecule roles</h2>
<table class="ce-molecule-matrix">
    <thead>
    <tr>
        <th>Investigation type</th>
    @foreach($distinctRoles as $role)
        <th>{{$role}}</th>
    @endforeach
    </tr>
    </thead>
    <tbody>
@foreach($matrix as $type => $row)
    <tr>
        <td><a href="{{Title::newFromText($type, NS_CATEGORY)->getFullURL()}}">{{Title::newFromText($type, NS_CATEGORY)->getText()}}</a></td>
        @foreach($distinctRoles as $role)
            @if(isset($row[$role]))
                <td>
                    <img class="ce-inv-record" title="click to see investigation records below" role="{{$role}}" src="{{$wgScriptPath."/extensions/ChemExtension/skins/images/check.png"}}"/>
                    <div class="ce-inv-record-content" style="display: none">
                        <ul>
                    @foreach($row[$role] as $title)
                        <li class="ce-inv-record-item"><a target="_blank" href="{{$title->getFullURL()}}">{{$title->getSubpageText()}}</a></li>

                    @endforeach
                        </ul>
                    </div>
                </td>
            @else
                <td><img src="{{$wgScriptPath."/extensions/ChemExtension/skins/images/cross.png"}}"/></td>
            @endif

        @endforeach
    </tr>
@endforeach
    </tbody>
</table>
<div id="ce-inv-record-view" style="display: none">
    <h3>Records for role "<span id="ce-inv-record-role"></span>"</h3>
    <div id="ce-inv-record-view-content"></div>
</div>