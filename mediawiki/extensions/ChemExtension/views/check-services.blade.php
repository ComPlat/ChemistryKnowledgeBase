<div>
    <p>
        This page checks the external services required for this wiki.
    </p>
    <table style="width: 80%; border-collapse: separate; border-spacing: 1em;">
        @foreach($responses as $id => $res)
        <tr>
            <td><span style="font-weight: bold">{{$servicesData[$id]['name']}}</span> (contact: {{$servicesData[$id]['contact']}})</td>
            <td class="check-service-state {{$res === true ? 'check-service-state-ok' : 'check-service-state-not-ok'}}">
                <div title="{{$res === true ? "" : $res}}">{{$res === true ? 'OK': 'ERROR'}}</div>
            </td>
        </tr>
        @endforeach

        <tr>
            <td><span style="font-weight: bold">Open-AI service</span> (contact: kuehn (at) diqa.de)</td>
            <td class="check-service-state {{$openAIState === true ? 'check-service-state-ok' : 'check-service-state-not-ok'}}">
               <div title="{{$openAIState === true ? "": $openAIState}}">{{$openAIState === true ? 'OK': 'ERROR'}}</div>
            </td>
        </tr>
    </table>
</div>