<div class="ce-show-rests-container"><span class="ce-show-rests-button">[Show rests]</span>
    <div class="ce-show-table" style="display: none;">
        <table width="100%">
            <tr>
                @foreach($headers as $h)
                    <th>{{strtoupper($h)}}</th>
                @endforeach
            </tr>
            @foreach($rests as $row)
                <tr>
                    @foreach($row as $column => $value)
                    <td>{{$value}}</td>
                    @endforeach
                </tr>
            @endforeach
        </table>
    </div>
</div>