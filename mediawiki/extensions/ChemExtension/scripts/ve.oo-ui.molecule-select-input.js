
( function () {


    OO.ui.MoleculeSelectWidget = function MwWidgetsMoleculeSelectWidget( config ) {

        config = config || {};


        // Parent constructor
        OO.ui.MoleculeSelectWidget.parent.call( this, $.extend( true,
            {
                clearInputOnChoose: true,
                inputPosition: 'inline',
                allowEditTags: false,
                allowArbitrary: true,
                menu: {
                    filterMode: 'substring'
                }
            },
            config

        ) );
        OO.ui.mixin.PendingElement.call( this, $.extend( true,
            {

            },
            config ));
        // Initialization


    };

    /* Setup */

    OO.inheritClass( OO.ui.MoleculeSelectWidget, OO.ui.ComboBoxInputWidget );
    OO.mixinClass( OO.ui.MoleculeSelectWidget, OO.ui.mixin.PendingElement );

    /* Methods */

    /**
     * @inheritdoc
     */
    var handle;
    var lastRequest = null;

    OO.ui.MoleculeSelectWidget.prototype.onInputChange = function (text) {

        let widget = this;
        let ajax = new window.ChemExtension.AjaxEndpoints();

        if (handle) {
            clearTimeout(handle);
        }
        if (lastRequest) {
            lastRequest.abort();
        }

        handle = setTimeout(() =>  {
            widget.pushPending();

            lastRequest = ajax.searchForMolecule(text).done((response) => {
                let data = response.pfautocomplete;

                widget.menu.clearItems();
                let options = [];
                let option = new OO.ui.TitleMenuOptionWidget({
                    data: text,
                    label: text

                });
                options.push(option);
                for(let i = 0; i < data.length; i++) {
                    option = new OO.ui.TitleMenuOptionWidget({
                        data: data[i].title,
                        label: data[i].label

                    });
                    options.push(option);
                }
                widget.menu.addItems( options );
                widget.menu.toggle(true);
            }).always(() => {
                widget.popPending();
                OO.ui.MoleculeSelectWidget.parent.prototype.onInputChange.call( widget );
                lastRequest = null;
            });
        }, 300);



    };

    OO.ui.TitleMenuOptionWidget = function MwWidgetsNamespacesMenuOptionWidget( config ) {
        // Parent
        OO.ui.TitleMenuOptionWidget.parent.call( this, config );
        this.data = config.data;
    };

    /* Setup */

    OO.inheritClass( OO.ui.TitleMenuOptionWidget, OO.ui.MenuOptionWidget );

    OO.ui.TitleMenuOptionWidget.prototype.getLabel = function () {
        return this.data;
    };
    /**
     * @inheritdoc
     */
    OO.ui.TitleMenuOptionWidget.prototype.getMatchText = function () {
        return this.getLabel();
    };


}() );
