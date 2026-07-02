// Tour of search features
( function ( window, document, $, mw, gt ) {
    // tour.step.name are assigned here as well as tour.name
    let tour_name = "search_tour"
    let tour = new gt.TourBuilder({
        name: tour_name
    });


    // Information defining each tour step

    tour.firstStep( {
        name: "intro",
        title: 'Welcome',
        description: 'Welcome to the Introduction tour for Search page',
        overlay: false,
        closeOnClickOutside: false ,
    } )
        .next( function() {
            gt.setTourCookie( tour_name, "fulltext_search" );
            window.location.href = mw.util.getUrl( 'Special:Search' ) ;})

    tour.step({
        name:"fulltext_search",
        title:"Fulltext search",
        description:"Type your fulltext search query into here.",
        attachTo:"#fs-searchbar-button-text",
        position: "top",
        closeOnClickOutside: false,

    })
        .next("link_to_search")
        .back("intro")

    tour.step({
        name:"link_to_search",
        title:"Link to Search",
        description:"Use this feature to either bookmark the link or to copy and share the search with a colleague.",
        attachTo:".fs-save-search-link > a",
        position: "topLeft",
        closeOnClickOutside: false,
    })
        .back("fulltext_search")
        .next("sort_by")

    tour.step({
        name:"sort_by",
        title:"Sort By",
        description:"This drop down menu will sort the results by either relevance , the time it was published, and alphabetically by title",
        attachTo:"#sort-order-select-control",
        position: "bottom",
        closeOnClickOutside: false,
    })
        .next("tag_search")
        .back("link_to_search")


    tour.step({
        name:"tag_search",
        title:"Tagged values",
        description:"Publication pages have a list of tags. Clicking on a tag will filter the search to only include " +
            "results with that tag.",
        attachTo:".tag-cloud",
        position: "topLeft",
        closeOnClickOutside: false,
    })
        .back("sort_by")
        .next("selected_facets")

    tour.step({
        name:"selected_facets",
        title:"Selected Facets",
        description:"Here you can see the currently selected facets that are filtering the current result set. You can" +
            " remove a facet by clicking on the trash bin next to it.",
        attachTo:"#fs-facets > div:nth-child(1)",
        position: "right",
        closeOnClickOutside: false,
    })
        .next("category")
        .back("tag_search")

    tour.step({
        name:"category",
        title:"Category",
        description:"Here you can restrict the search to a specific category. Clicking on the category will filter the search to only include results from that category.",
        attachTo:"#fs-category-dropdown",
        position: "left",
        closeOnClickOutside: false,
    })
        .next("topics")
        .back("selected_facets")

    tour.step({
        name:"topics",
        title:"Topics",
        description:"Here you can restrict the search to a specific topic. Clicking on the topic will filter the search to only include results from that topic.",
        attachTo:"#fs-category-tree",
        position: "left",
        closeOnClickOutside: false,
    })
        .next("available_facets")
        .back("category")

    tour.step({
        name:"available_facets",
        title:"Available facets",
        description:"Here you can see the available facets that can be used to filter the search results. Clicking on a " +
            "facet will add it to the selected facets list. When you expand a facet you will see the available values. ",
        attachTo:"#fs-facetview",
        position: "right",
        closeOnClickOutside: false,
    })
        .next("results")
        .back("topics")

    tour.step({
        name:"results",
        title:"Results",
        description:"Here are the pages found by the current search. Clicking on the this will take you to the relevant page.",
        attachTo:"#fs-results",
        position: "top",
        closeOnClickOutside: false,

    }).transition(function () {
        if (gt.isEditing()) {
            return 'pub_feat';
        }
        gt.endTour();  // remove the cookie, tour won't reopen
        return gt.TransitionAction.HIDE;
    })
        .back("available_facets")


// The following should be the last line of your tour.
} ( window, document, jQuery, mediaWiki, mediaWiki.guidedTour ) );
