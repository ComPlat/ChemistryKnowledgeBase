@if(!is_null($publicationPageForConcreteMolecule))
    <p>The molecule template was defined here:
        <a target="_blank" href="{{$publicationPageForConcreteMolecule->getFullURL()}}">{{$publicationPageForConcreteMolecule->getText()}}</a>
    </p>
@endif
<h2>Molecule is used on following pages</h2>
@if(count($topicPages) > 0)
    <div class="ce-page-type ce-page-type-topic" style="margin-top: 15px">topic</div>
    <ul>
        @foreach($topicPages as $p)
            <li><a target="_blank" href="{{$p->getFullURL()}}">{{$p->getText()}}</a></li>
        @endforeach
    </ul>
@endif
@if(count($publicationPages) > 0)
    <div class="ce-page-type ce-page-type-publication" style="margin-top: 15px">publication</div>
    <ul>
        @foreach($publicationPages as $p)
            <li><a target="_blank" href="{{$p->getFullURL()}}">{{$p->getText()}}</a></li>
        @endforeach
    </ul>
@endif
@if(count($investigationPages) > 0)
    <div class="ce-page-type ce-page-type-investigation" style="margin-top: 15px">investigation</div>
    <ul>
        @foreach($investigationPages as $p)
            <li><a target="_blank" href="{{$p->getFullURL()}}">{{$p->getText()}}</a></li>
        @endforeach
    </ul>
@endif
@if(count($otherPages) > 0)
    <div class="ce-page-type ce-page-type-other" style="margin-top: 15px">other</div>
    <ul>
        @foreach($otherPages as $p)
            <li><a target="_blank" href="{{$p->getFullURL()}}">{{$p->getText()}}</a></li>
        @endforeach
    </ul>
@endif