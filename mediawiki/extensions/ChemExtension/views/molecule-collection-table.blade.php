<div class="ce-show-molecule-collection">
    @if(count($rows) === 0)
        <p>No molecules found.</p>
    @else
    <table width="100%">
        <tr>
            <th>Molecule</th>
            <th>Publication</th>
            @foreach($rGroups as $r)
                <th>{{strtoupper($r)}}</th>
            @endforeach
        </tr>
        @foreach($rows as $row)
            <tr>
                <td><a target="_blank" href="{{$row['molecule']->getFullURL()}}">{{$row['molecule']->getText()}}</a></td>
                <td>
                    @if(count($row['publications']) === 1)
                        <a target="_blank" href="{{reset($row['publications'])->getFullURL()}}">{{reset($row['publications'])->getText()}}</a>
                    @else
                        <ul>
                        @foreach($row['publications'] as $p)
                                <li><a target="_blank" href="{{$p->getFullURL()}}">{{$p->getText()}}</a></li>
                        @endforeach
                        </ul>
                    @endif

                </td>
                @foreach($rGroups as $r)
                    <td>
                        {{$row['rGroups'][$r]}}
                    </td>
                @endforeach
            </tr>
        @endforeach
    </table>
    @endif
</div>