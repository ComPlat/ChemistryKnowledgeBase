<div>
    <p>
        This page checks the external services required for this wiki.
    </p>
    <table style="width: 80%">
        <tr>
            <td><span style="font-weight: bold">R-Groups-Service</span> (contact: caman.nguyenthanh (at) gmail.com)</td>
            <td class="check-service-state {{$RGroupState === true ? 'check-service-state-ok' : 'check-service-state-not-ok'}}">
                {{$RGroupState === true ? 'OK': $RGroupState}}
            </td>
        </tr>
        <tr>
            <td><span style="font-weight: bold">Molecule render service</span> (contact: pierre.tremouilhac (at) kit.edu)</td>
            <td class="check-service-state {{$renderState === true ? 'check-service-state-ok' : 'check-service-state-not-ok'}}">
                {{$renderState === true ? 'OK': $renderState}}
            </td>
        </tr>
        <tr>
            <td><span style="font-weight: bold">TIB service</span> (contact: kuehn (at) diqa.de)</td>
            <td class="check-service-state {{$tibState === true ? 'check-service-state-ok' : 'check-service-state-not-ok'}}">
                {{$tibState === true ? 'OK': $tibState}}
            </td>
        </tr>
    </table>
</div>