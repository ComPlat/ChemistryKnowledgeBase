<div class="ce-show-rgroups-container" style="float:{{$arguments['float']}};"><span class="ce-show-rgroups-button">[Show R-Groups]</span>
    <div class="ce-show-table" style="display: none;">
        <table width="100%">
            @if(count($moleculesToDisplay) === 0)
                <tr><td>Molecules need to be generated. Re-load the page in a minute.</td></tr>
            @else
            <tr>
                <th>Molecule</th>
                @foreach($headers as $h)
                    <th>{{strtoupper($h)}}</th>
                @endforeach
            </tr>
            @foreach($moleculesToDisplay as $row)
                <tr>

                    <td><a target="_blank" href="{{$row['moleculePage']->getFullURL()}}">{{$row['moleculePage']->getText()}}</a></td>
                    @foreach($row['rGroups'] as $column => $value)
                        <td>{{$value}}</td>
                    @endforeach
                </tr>
            @endforeach
            @endif
        </table>
    </div>
</div>