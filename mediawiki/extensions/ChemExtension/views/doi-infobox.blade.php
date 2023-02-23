|doi={{$doi}}
|title={!! $title !!}
|type={{$type}}
|authors= @if(count($authors) === 0)
-
@else
{{implode(", ", array_map(function($e) { return $e['name']; }, $authors))}}
@endif
|submittedAt={{$submittedAt}}
|publishedOnlineAt={{$publishedOnlineAt}}
|publishedPrintAt={{$publishedPrintAt}}
|publisher={{$publisher}}
|licenses=@if(count($licenses) === 0)
-
@else
{{implode(", ", array_map(function($e) { return '['.$e['URL'].']'; }, $licenses))}}
@endif
|year={{$year}}
|issue={{$issue}}
|journal={{$journal}}
|volume={{$volume}}
|pages={{$pages}}
|subjects=@if(count($subjects) === 0)
-
@else
{{implode(", ", array_map(function($e) { return $e; }, $subjects))}}
@endif
|funders=@if(count($funders) === 0)
-
@else
{{implode(", ", array_map(function($e) { return $e; }, $funders))}}
@endif