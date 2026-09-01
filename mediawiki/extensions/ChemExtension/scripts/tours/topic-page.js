// Tour of search features
( function ( window, document, $, mw, gt ) {
    // tour.step.name are assigned here as well as tour.name
    const tour_name = "topic_page"
    // Start tour
    const tour = new gt.TourBuilder( {
        name: tour_name
    } );

    tour.firstStep( {
        name: "intro",
        title: 'Welcome',
        description: 'Welcome to the Introduction tour for MediaWiki',
        overlay: false,
        closeOnClickOutside: false,

    } )
        .next( function() {
            gt.setTourCookie( tour_name, "topic_intro" );
            window.location.href = mw.util.getUrl( 'Category:Homogeneous_photocatalytic_CO2_conversion' ) ;})

    tour.step( {
        name: "topic_intro",
        title: 'Welcome to the Topic Page',
        description: 'Welcome to the topic page here you can see a collection of projects that share a common goal.',
    } )
        .next( "toc" )
        .back( function() {
            gt.setTourCookie( tour_name, "topic_intro" );
            window.location.href = mw.util.getUrl( 'Main_Page' ) ;});

    tour.step({
        name:"toc",
        title:"Table of Contents",
        description:"Here is the table of contents clicking on any link in here will take you to relevant spot on the page",
        attachTo:"#mw-toc-heading",
        position: "top",
        closeOnClickOutside: false,
    })
        .next("scope")
        .back("topic_intro")

    tour.step({
        name:"scope",
        title:"Scope",
        description:"The scope explains the criteria of the topic. This page is a subtopic of the higher level topics " +
            "of CO2 conversion.",
        attachTo:"#Scope_of_this_topic_and_related_important_content",
        position: "right",
        closeOnClickOutside: false,
    })
        .next("distinction")
        .back("toc")

    tour.step({
        name:"distinction",
        title:"Distinction",
        description:"This section explains the difference of this topic from other topics. It also contains the embedded" +
            " data from investigations in the publications page.",
        attachTo:"#Distinction_from_other_articles_within_the_topic_Photocatalytic_CO2_conversion",
        position: "bottom",
        closeOnClickOutside: false,
    })
        .next(() => {
            $('.experiment-link-control-bar button').eq(0).trigger('click');
            return "table_2";
        })
        .back("scope")

    tour.step({
        name:"table_2",
        title:"Investigation Table",
        description:"This investigation table contains an aggregated list of experiments that are part of the topic. " +
            "The content comes from the investigations of the publications pages. The table is sortable by clicking on the column headers. " +
            "Double-clicking on the table top row will collapse the column. This is useful on tables with a large number of columns",
        attachTo:".experiment-link-control-bar button",
        position: "bottomRight",
        closeOnClickOutside: false,
    })
        .back("distinction")
        .next("publications_2")

    tour.step({
        name:"publications_2",
        title:"Publications",
        description:"In this section publications that correspond to the topic are listed alphabetically by their title",
        attachTo:".chem_ext_literature",
        position: "topLeft",
        closeOnClickOutside: false,
    }).transition(function () {
        if (gt.isEditing()) {
            return 'pub_feat';
        }
        gt.endTour();  // remove the cookie, tour won't reopen
        return gt.TransitionAction.HIDE;
    })
        .back("table_2")

} ( window, document, jQuery, mediaWiki, mediaWiki.guidedTour ) );
