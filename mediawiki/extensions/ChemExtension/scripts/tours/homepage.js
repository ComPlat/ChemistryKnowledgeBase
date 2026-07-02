/*
 * Guided Tour to test guided tour features.
 */
// Copy the next line into the start of your tour.

(function (window, document, $, mw, gt) {

    function open_nav() {
        $('#ce-side-panel-content-collapsed').hide();
        $('#ce-side-panel-content').show();
        $('div.container-fluid div.row').attr('style', 'margin-left: 400px !important;');
        return "m_topic"
    }

    let tour = new gt.TourBuilder({
        name: 'homepage'
    });

    tour.firstStep({
        name: 'intro',
        title: 'Welcome',
        description: 'Welcome to the Introduction tour for MediaWiki',
        overlay: true
    })
        .next(function () {
            gt.setTourCookie('homepage', 'homebutton');
            window.location.href = mw.util.getUrl('Main_Page');
        });


    tour.step({
        name: 'homebutton',
        title: 'Home Button',
        description: 'Here is the home button click here to return to the home page',
        attachTo: '#mw-navigation a',
        position: 'bottomRight',
        closeOnClickOutside: false,
    })
        .next('edit_trans')
        .back('intro');

    tour.step({
        name: 'edit_trans',
        title: 'Edit the whole page…',
        description: 'Click the \"Edit\" button to make your changes.',
        attachTo: '#ca-ve-edit',
        position: 'bottom',
        closeOnClickOutside: false,

    }).next('feat_mol');


    tour.step({
        name: 'feat_mol',
        title: 'Featured Molecule',
        description: 'Clicking this will bring you to the relevant molecule page. From here you can see experiments that use the selected molecule.',
        overlay: false,
        attachTo: 'table tr:nth-child(2) td:nth-child(2)',
        position: 'left',
        closeOnClickOutside: false,

    })  .next(open_nav)
        .back('homebutton');


    tour.step({
        name: "m_topic",
        title: "Navigation sidebar",
        description: "Here is the navigations sidebar. It allows you to navigate through the site. " +
            "Therefore you can see a structure of the site's content as well as the means to search for content",
        overlay: true,
        attachTo: 'div.CategoryTreeItem:first-child',
        position: 'right',
        closeOnClickOutside: false,

    })
        .next("testa")
        .back("feat_mol");


    tour.step({
        name: "testa",
        title: "Topics",
        description: "This is the highest level of organization. Topics are the main feature to structure the publications" +
            " of the site. They are hierarchically organized. If you select a topic you will go to the topic page where " +
            "you find an overview about the topic " +
            "and you will see a list of publications that are part of that topic.",
        attachTo: '#ce-topic-element',
        position: 'bottomRight',
        closeOnClickOutside: false,

    })
        .next("pub_nav")
        .back("m_topic");


    tour.step({
        name: "pub_nav",
        title: "Publications",
        description: "This is the publication level. Publications belong to topics. You can see a list of publications " +
            "that are part of that topic if you click on the 'publication'-button right here. You are also able to search" +
            " a publication by the title. The search scope is limited to the publications that are part of the topic page you are currently on.",
        attachTo: '#ce-publication-element',
        position: 'right',
        closeOnClickOutside: false,
    })
        .next("inv_nav")
        .back("testa");


    tour.step({
        name: "inv_nav",
        title: "Investigations",
        description: "Investigations are experiments conducted in a publication. You can see a list of investigations " +
            "when you click on the 'investigation'-button right here. The scope of the search is limited to the publication or to the " +
            "publications that are part of the topic page you are currently on.",
        attachTo: '#ce-investigation-element',
        position: 'right',
        closeOnClickOutside: false,
    })
        .next("mol_nav")
        .back("pub_nav");

    tour.step({
        name: "mol_nav",
        title: "Molecules",
        description: "Molecules are the building blocks of investigations. You can see a list of molecules that are " +
            "relevant to the current context (topic or publication). You can search for molecules by their name or by their chemical formula. ",
        attachTo: '#ce-molecule-element',
        position: 'right',
        closeOnClickOutside: false,
    })
        .next("search_nav")
        .back("inv_nav");

    tour.step({
        name: "search_nav",
        title: "search bar",
        description: "Here if you click on the search bar and hit enter will allow you to entered faceted search",
        attachTo: "#searchInput",
        position: "bottom",
        closeOnClickOutside: false,
        allowAutomaticNext: false,
    })
        .back("inv_nav");


}(window, document, jQuery, mediaWiki, mediaWiki.guidedTour));
