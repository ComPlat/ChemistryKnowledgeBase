// Tour of search features
( function ( window, document, $, mw, gt ) {
    // tour.step.name are assigned here as well as tour.name
    const tour_name = "publicationpage"

    let tour = new gt.TourBuilder( {
        name: tour_name
    } );


    tour.firstStep( {
        name: "intro",
        title: 'Welcome',
        description: 'Welcome to the Introduction tour for MediaWiki',
        overlay: false,
        closeOnClickOutside: false ,
    } )
        .next( function() {
            gt.setTourCookie( tour_name, "pub_intro" );
            window.location.href = mw.util.getUrl( 'Photochemical_Reduction_of_Carbon_Dioxide_to_Formic_Acid_using_Ruthenium(II)-Based_Catalysts_and_Visible_Light' ) ;})

    tour.step( {
        name: "pub_intro",
        title: 'Welcome to an example publication page',
        description: 'Welcome to the Publications page here is where information from the paper is extracted into the tables on the page.',
        closeOnClickOutside: false,
    } )
        .next( "toc" )
        .back( function() {
            gt.setTourCookie( tour_name, "pub_intro" );
            window.location.href = mw.util.getUrl( 'Main_Page' ) ;});

    tour.step({
        name:"toc",
        title:"Table of Contents",
        description:"Here is the table of contents that contains the sections for the page.",
        attachTo:"#toc",
        position: "right",
        closeOnClickOutside: false,

    })
        .next("about")
        .back("pub_intro")

    tour.step({
        name:"about",
        title:"About Box",
        description:"Here is the about table clicking on  the bar expands the tale showing metadata about the paper",
        attachTo:".infobox > tbody:nth-child(1) > tr:nth-child(1) > th:nth-child(1)",
        position: "top",
        closeOnClickOutside: false,
    })
        .next("catalyst")
        .back("toc")

    tour.step({
        name:"catalyst",
        title:"Sample section: Catalysts used in the publication",
        description:"Here is a sample section of the publication. You can easily create the document structure by " +
            "using the visual editor mode. In this case, you dont need to learn wikitext markup",
        attachTo:"#Catalysts",
        position: "topRight",
        closeOnClickOutside: false,
    })
        .next( "molecule")
        .back("about")

    tour.step({
        name:"molecule",
        title:"Molecules",
        description:"Here is a molecule extracted from the publication and drawn in Ketcher editor here in the wiki. " +
            "Clicking on the molecule will take you to the relevant molecule page. " +
            "You can edit the molecule by entering visual edit mode, then click on the molecule and choose 'edit' and then " +
            "press the 'Ketcher'-button.",
        attachTo:".chemform:nth-child(1)",
        position: "topRight",
        closeOnClickOutside: false,
    })
        .next( "investigations")
        .back("catalyst")

    tour.step({
        name: "investigations",
        title:"Investigation",
        description:"Here is a table with each experiment extracted into a unique investigation.",
        attachTo:"#Investigations",
        position: "topRight",
        closeOnClickOutside: false,
    })
        .next("investigation_table")
        .back("catalyst")

    tour.step({
        name:"investigation_table",
        title:"Investigations",
        description:"Here is the  list of different investigations from the publication.",
        attachTo:"#mw-content-text > ul",
        position: "topLeft",
        closeOnClickOutside: false,

    }).transition(function () {
        if (gt.isEditing()) {
            return 'pub_feat';
        }
        gt.endTour();  // remove the cookie, tour won't reopen
        return gt.TransitionAction.HIDE;
    })
        .back( "investigations")

// The following should be the last line of your tour.
} ( window, document, jQuery, mediaWiki, mediaWiki.guidedTour ) );
