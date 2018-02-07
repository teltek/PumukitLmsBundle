YUI.add('moodle-atto_pumukitpr-button', function (Y, NAME) {

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/*
 * @package    atto_pumukitpr
 * @copyright  2013 Damyon Wiese  <damyon@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module moodle-atto_pumukitpr_alignment-button
 */

/**
 * Atto pumukitpr selection tool.
 *
 * @namespace M.atto_pumukitpr
 * @class Button
 * @extends M.editor_atto.EditorPlugin
 */



var COMPONENTNAME = 'atto_pumukitpr';
var FLAVORCONTROL = 'pumukitpr_flavor';
var LOGNAME = 'atto_pumukitpr';

var CSS = {
    INPUTSUBMIT: 'atto_media_urlentrysubmit',
    INPUTCANCEL: 'atto_media_urlentrycancel',
    FLAVORCONTROL: 'flavorcontrol'
    },
    SELECTORS = {
        FLAVORCONTROL: '.flavorcontrol'
    };

var TEMPLATE = '' +
    '<ul class="root nav nav-tabs" role="tablist">' +
        '<li class="nav-item">' +
            '<a class="nav-link" href="#{{elementid}}_upload" role="tab" data-toggle="tab">' +
                'Upload' +
            '</a>' +
        '</li>' +
        '<li class="nav-item">' +
            '<a class="nav-link" href="#{{elementid}}_personal_recorder" role="tab" data-toggle="tab">' +
                'Personal Recorder' +
            '</a>' +
        '</li>' +
        '<li class="nav-item">' +
            '<a class="nav-link active" href="#{{elementid}}_manager" role="tab" data-toggle="tab">' +
                'My Videos' +
            '</a>' +
        '</li>' +
    '</ul>' +
    '<div class="root tab-content">' +
        '<div class="tab-pane" id="{{elementid}}_upload">' +

            '<iframe src="{{PUMUKITURL}}/openedx/sso/upload?hash={{HASH}}&username={{USERNAME}}&lang=en" ' +
                    'frameborder="0" allowfullscreen style="width:100%;height:80vh">' +
           '</iframe>' +

        '</div>' +
        '<div data-medium-type="personal_recorder" class="tab-pane" id="{{elementid}}_personal_recorder">' +

            '<iframe id="pumukitpr_iframe_recorder" src="{{PUMUKITURL}}/openedx/sso/personal_recorder?hash={{HASH}}&username={{USERNAME}}&lang=en" ' +
                    'frameborder="0" allowfullscreen style="width:100%;height:80vh" allow="microphone; camera">' +
           '</iframe>' +


        '</div>' +
        '<div class="tab-pane active" id="{{elementid}}_manager">' +

            '<iframe src="{{PUMUKITURL}}/openedx/sso/manager?hash={{HASH}}&username={{USERNAME}}&lang=en" ' +
                    'frameborder="0" allowfullscreen style="width:100%;height:80vh">' +
           '</iframe>' +

        '</div>' +
    '</div>' +



    '<form class="atto_form">' +
        '<input class="{{CSS.FLAVORCONTROL}}" id="{{elementid}}_{{FLAVORCONTROL}}" ' +
            'name="{{elementid}}_{{FLAVORCONTROL}}" value="{{defaultflavor}}" ' +
            'type="hidden" />' +
    '</form>';

Y.namespace('M.atto_pumukitpr').Button = Y.Base.create('button', Y.M.editor_atto.EditorPlugin, [], {

    _receiveMessageBind: null,

    /**
     * Initialize the button
     *
     * @method Initializer
     */
    initializer: function() {
        // If we don't have the capability to view then give up.
        if (this.get('disabled')){
            return;
        }

        this.addButton({
            icon: 'e/insert_edit_video',
            //icon: 'icon',
            //iconComponent: 'atto_pumukitpr',
            buttonName: 'pumukitpr',
            callback: this._displayDialogue,
            callbackArgs: 'iconone'
        });


        // Force SSO
        var id = "pumukitpr_iframe_sso";
        if (!document.getElementById(id)) {
            var iframe = document.createElement('iframe');
            iframe.id = id;
            iframe.style.display = "none";
            iframe.src = this.get('pumukitprurl') + "/openedx/sso/manager?hash=" +
                this.get('hash') + "&username=" +
                this.get('username') + "&email="+
                this.get('email') + "&lang=en";
            iframe.allow = "microphone; camera";
            document.getElementsByTagName('body')[0].appendChild(iframe);
        }


    },

    /**
     * Get the id of the flavor control where we store the ice cream flavor
     *
     * @method _getFlavorControlName
     * @return {String} the name/id of the flavor form field
     * @private
     */
    _getFlavorControlName: function(){
        return(this.get('host').get('elementid') + '_' + FLAVORCONTROL);
    },


     /**
     * Display the pumukitpr Dialogue
     *
     * @method _displayDialogue
     * @private
     */
    _displayDialogue: function(e, clickedicon) {
        e.preventDefault();
        var width=900;
        
        this._receiveMessageBind = this._receiveMessage.bind(this);
        window.addEventListener('message', this._receiveMessageBind);

        var dialogue = this.getDialogue({
            headerContent: this.get('dialogtitle'),
            //width: width + 'px',
            widht: '70%', //rr width
            focusAfterHide: clickedicon
        });
        //dialog doesn't detect changes in width without this
        //if you reuse the dialog, this seems necessary
        if(dialogue.width !== width + 'px'){
            dialogue.set('width',width+'px');
            dialogue.set('max-width','550px');
        }

        //append buttons to iframe
        var buttonform = this._getFormContent(clickedicon);

        var bodycontent =  Y.Node.create('<div></div>');
        bodycontent.append(buttonform);

        //set to bodycontent
        dialogue.set('bodyContent', bodycontent);
        dialogue.show();
        this.markUpdated();

        // Add listen event to close on.
        var clickButton = document.getElementsByClassName('closebutton');
        if (clickButton[0]) {
            clickButton[0].addEventListener('click', this._closeSharedWindow);
        }
    },


     /**
     * Return the dialogue content for the tool, attaching any required
     * events.
     *
     * @method _getDialogueContent
     * @return {Node} The content to place in the dialogue.
     * @private
     */
    _getFormContent: function(clickedicon) {
        var template = Y.Handlebars.compile(TEMPLATE),
            content = Y.Node.create(template({
                elementid: this.get('host').get('elementid'),
                CSS: CSS,
                FLAVORCONTROL: FLAVORCONTROL,
                PUMUKITURL: this.get('pumukitprurl'),
                HASH: this.get('hash'),
                USERNAME: this.get('username'),
                component: COMPONENTNAME,
                defaultflavor: this.get('defaultflavor'),
                clickedicon: clickedicon
            }));

        this._form = content;
        //this._form.one('.' + CSS.INPUTSUBMIT).on('click', this._doInsert, this);

        return content;
    },

    /**
     * Inserts the users input onto the page
     * @method _getDialogueContent
     * @private
     */
    _doInsert : function(e){
        e.preventDefault();
        this.getDialogue({
            focusAfterHide: null
        }).hide();

        var flavorcontrol = this._form.one(SELECTORS.FLAVORCONTROL);

        // If no file is there to insert, don't do it.
        if (!flavorcontrol.get('value')){
            return;
        }

        this.editor.focus();
        this.get('host').insertContentAtFocusPoint(flavorcontrol.get('value'));
        this.markUpdated();

    },


    _receiveMessage : function(e){
        if (!('mmId' in event.data)) {
            return;
        }

        e.preventDefault();
        this.getDialogue({
            focusAfterHide: null
        }).hide();

        this._closeSharedWindow(e);

        // If no file is there to insert, don't do it.
        if (!e.data.mmId){
            return;
        }

        window.removeEventListener('message', this._receiveMessageBind);

        this.editor.focus();

        var url = this.get('pumukitprurl') + '/openedx/openedx/embed/?id=' + event.data.mmId;
        var iframe = '<iframe src="' + url +
            '" style="border:0px #FFFFFF none;box-shadow:0 3px 10px rgba(0,0,0,.23), 0 3px 10px rgba(0,0,0,.16);"' +
            ' scrolling="no" frameborder="1" height="270" width="480" allowfullscreen></iframe>';
        this.get('host').insertContentAtFocusPoint(iframe);
        this.markUpdated();
    },


    _closeSharedWindow : function(e){
        var sharedWindow = document.getElementById('pumukitpr_iframe_recorder');
        sharedWindow.parentNode.removeChild(sharedWindow);
    }
}, {
    ATTRS: {
        pumukitprurl: {
            value: ''
        },
        hash: {
            value: ''
        },
        username: {
            value: ''
        },
        email: {
            value: ''
        },
        dialogtitle: {
            value: ''
        }
    }
});


}, '@VERSION@', {"requires": ["moodle-editor_atto-plugin"]});
