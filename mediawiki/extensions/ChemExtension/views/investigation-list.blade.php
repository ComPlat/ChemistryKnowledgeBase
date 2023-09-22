<h2>Investigations</h2>
<ul>
@foreach($list as $i)
  <li><a href="{{$i['title']->getFullURL()}}">{{$i['title']->getSubpageText()}}</a> <span>({{\DIQA\ChemExtension\Utils\WikiTools::getInvestigationCategoriesAsString($i['title'])}})</span></li>
@endforeach
</ul>