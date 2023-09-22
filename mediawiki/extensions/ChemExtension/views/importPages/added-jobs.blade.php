<p class="added-jobs-success">Import jobs were successfully created.</p>
<ul>
@foreach($jobs as $jobTitleAsText => $data)
    <li>
        {{$data['main']->getTitle()->getPrefixedText()}}
        @if(count($data['subPages']) > 0)
            (with the following investigations:
        {{implode(',', array_map(fn($subPage) => $subPage->getTitle()->getSubpageText(), $data['subPages']))}} )
        @endif
    </li>
@endforeach
</ul>