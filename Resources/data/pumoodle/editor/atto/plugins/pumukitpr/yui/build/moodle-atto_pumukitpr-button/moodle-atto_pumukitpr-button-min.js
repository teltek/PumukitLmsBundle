YUI.add("moodle-atto_pumukitpr-button",function(e,t){var n="atto_pumukitpr",r="pumukitpr_flavor",i="atto_pumukitpr",s={INPUTSUBMIT:"atto_media_urlentrysubmit",INPUTCANCEL:"atto_media_urlentrycancel",FLAVORCONTROL:"flavorcontrol"},o={FLAVORCONTROL:".flavorcontrol"},u='<ul class="root nav nav-tabs" role="tablist"><li class="nav-item"><a class="nav-link" href="#{{elementid}}_upload" role="tab" data-toggle="tab">Upload</a></li><li class="nav-item"><a class="nav-link" href="#{{elementid}}_personal_recorder" role="tab" data-toggle="tab">Personal Recorder</a></li><li class="nav-item"><a class="nav-link active" href="#{{elementid}}_manager" role="tab" data-toggle="tab">My Videos</a></li></ul><div class="root tab-content"><div class="tab-pane" id="{{elementid}}_upload"><iframe src="{{PUMUKITURL}}/openedx/sso/upload?hash={{HASH}}&username={{USERNAME}}&lang=en" frameborder="0" allowfullscreen style="width:100%;height:80vh"></iframe></div><div data-medium-type="personal_recorder" class="tab-pane" id="{{elementid}}_personal_recorder"><iframe src="{{PUMUKITURL}}/openedx/sso/personal_recorder?hash={{HASH}}&username={{USERNAME}}&lang=en" frameborder="0" allowfullscreen style="width:100%;height:80vh"></iframe></div><div class="tab-pane active" id="{{elementid}}_manager"><iframe src="{{PUMUKITURL}}/openedx/sso/manager?hash={{HASH}}&username={{USERNAME}}&lang=en" frameborder="0" allowfullscreen style="width:100%;height:80vh"></iframe></div></div><form class="atto_form"><input class="{{CSS.FLAVORCONTROL}}" id="{{elementid}}_{{FLAVORCONTROL}}" name="{{elementid}}_{{FLAVORCONTROL}}" value="{{defaultflavor}}" type="hidden" /></form>';e.namespace("M.atto_pumukitpr").Button=e.Base.create("button",e.M.editor_atto.EditorPlugin,[],{_receiveMessageBind:null,initializer:function(){if(this.get("disabled"))return;this.addButton({icon:"e/insert_edit_video",buttonName:"pumukitpr",callback:this._displayDialogue,callbackArgs:"iconone"});var e="pumukitpr_iframe_sso";if(!document.getElementById(e)){var t=document.createElement("iframe");t.id=e,t.style.display="none",t.src=this.get("pumukitprurl")+"/openedx/sso/manager?hash="+this.get("hash")+"&username="+this.get("username")+"&email="+this.get("email")+"&lang=en",document.getElementsByTagName("body")[0].appendChild(t)}},_getFlavorControlName:function(){return this.get("host").get("elementid")+"_"+r},_displayDialogue:function(t,n){t.preventDefault();var r=900;this._receiveMessageBind=this._receiveMessage.bind(this),window.addEventListener("message",this._receiveMessageBind);var i=this.getDialogue({headerContent:this.get("dialogtitle"),widht:"70%",focusAfterHide:n});i.width!==r+"px"&&(i.set("width",r+"px"),i.set("max-width","550px"));var s=this._getFormContent(n),o=e.Node.create("<div></div>");o.append(s),i.set("bodyContent",o),i.show(),this.markUpdated()},_getFormContent:function(t){var i=e.Handlebars.compile(u),o=e.Node.create(i({elementid:this.get("host").get("elementid"),CSS:s,FLAVORCONTROL:r,PUMUKITURL:this.get("pumukitprurl"),HASH:this.get("hash"),USERNAME:this.get("username"),component:n,defaultflavor:this.get("defaultflavor"),clickedicon:t}));return this._form=o,o},_doInsert:function(e){e.preventDefault(),this.getDialogue({focusAfterHide:null}).hide();var t=this._form.one(o.FLAVORCONTROL);if(!t.get("value"))return;this.editor.focus(),this.get("host").insertContentAtFocusPoint(t.get("value")),this.markUpdated()},_receiveMessage:function(e){if(!("mmId"in event.data))return;e.preventDefault(),this.getDialogue({focusAfterHide:null}).hide();if(!e.data.mmId)return;window.removeEventListener("message",this._receiveMessageBind),this.editor.focus();var t=this.get("pumukitprurl")+"/openedx/openedx/embed/?id="+event.data.mmId,n='<iframe src="'+t+'" style="border:0px #FFFFFF none;box-shadow:0 3px 10px rgba(0,0,0,.23), 0 3px 10px rgba(0,0,0,.16);"'+' scrolling="no" frameborder="1" height="270" width="480" allowfullscreen></iframe>';this.get("host").insertContentAtFocusPoint(n),this.markUpdated()}},{ATTRS:{pumukitprurl:{value:""},hash:{value:""},username:{value:""},email:{value:""},dialogtitle:{value:""}}})},"@VERSION@",{requires:["moodle-editor_atto-plugin"]});
