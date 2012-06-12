
/**
 * An autosuggest textbox control.
 * @class
 * @scope public
 */
function AutoSuggestControl(oForm,  oIDbox /*:HTMLSelectBoxInputElement*/, oTextboxCopy /*:HTMLInputElement*/, oTextbox /*:HTMLInputElement*/,
                            oProvider /*:SuggestionProvider*/, oCallback) {

    /**
     * The currently selected suggestions.
     * @scope private
     */   
    this.cur /*:int*/ = -1;

    /**
     * The dropdown list layer.
     * @scope private
     */
    this.layer = null;

    
    /**
     * Form
     * @scope private.
     */
    this.form /*:Form*/ = oForm;


    /**
     * Suggestion provider for the autosuggest feature.
     * @scope private.
     */
    this.provider /*:SuggestionProvider*/ = oProvider;
    
    /**
     * function to call once the item has been selected.
     */
    this.callback_func = oCallback;
    
    /**
     * The textbox to capture.
     * @scope private
     */
    this.textbox /*:HTMLInputElement*/ = oTextbox;

    /**
     * The textbox to fill with a copy of the selected data.
     * @scope private
     */
    this.textboxcopy /*:HTMLInputElement*/ = oTextboxCopy;


	/**
     * The select box to fill with ID.
     * @scope private
     */
    this.selectbox /*:HTMLSelectBoxInputElement*/ = oIDbox;


    //initialize the control
    this.init();
    
}

/**
 * Autosuggests one or more suggestions for what the user has typed.
 * If no suggestions are passed in, then no autosuggest occurs.
 * @scope private
 * @param aSuggestions An array of suggestion strings.
 * @param bTypeAhead If the control should provide a type ahead suggestion.
 */
AutoSuggestControl.prototype.autosuggest = function (aSuggestions /*:Array*/,
                                                     bTypeAhead /*:boolean*/) {

    //make sure there's at least one suggestion
    if (aSuggestions.length > 0) {
        if (bTypeAhead) {
           this.typeAhead(aSuggestions[0]);
        }
        
        this.showSuggestions(aSuggestions);
    } else {
        this.hideSuggestions();
    }
};

/**
 * Creates the dropdown layer to display multiple suggestions.
 * @scope private
 */
AutoSuggestControl.prototype.createDropDown = function () {

    var oThis = this;
    //create the layer and assign styles
    this.layer = document.createElement("div");
    this.layer.className = "suggestions";
    this.layer.style.display = "none";
    //this.layer.style.width = this.textbox.offsetWidth;
	this.layer.style.width = "300px";
    
    //when the user clicks on the a suggestion, get the text (innerHTML)
    //and place it into a textbox
    this.layer.onmousedown = 
    this.layer.onmouseup = 
    this.layer.onmouseover = function (oEvent) {
        oEvent = oEvent || window.event;
        oTarget = oEvent.target || oEvent.srcElement;

        if (oEvent.type == "mousedown") {
			oThis.highlightSuggestion(oTarget);
			oThis.hideSuggestions();
		} else if (oEvent.type == "mouseover") {
            // Turning this off as once you move your mouse
            // over a suggestion, it says highlighted. Then
            // when you click to lose focus, the highlighted suggestion
            // is selected
            
		    //oThis.highlightSuggestion(oTarget);
        } else {
            oThis.textboxcopy.focus();
        }
    };
    
    this.textbox.parentNode.appendChild(this.layer);
    //document.body.appendChild(this.layer);
};

/**
 * Gets the left coordinate of the textbox.
 * @scope private
 * @return The left coordinate of the textbox in pixels.
 */
AutoSuggestControl.prototype.getLeft = function () /*:int*/ {

    var oNode = this.textbox;
    var iLeft = 0;
    
    while(oNode.tagName != "BODY") {
        iLeft += oNode.offsetLeft;
        oNode = oNode.offsetParent;        
    }
    
    return iLeft;
};

/**
 * Gets the top coordinate of the textbox.
 * @scope private
 * @return The top coordinate of the textbox in pixels.
 */
AutoSuggestControl.prototype.getTop = function () /*:int*/ {

    var oNode = this.textbox;
    var iTop = 0;
    
    while(oNode.tagName != "BODY") {
        iTop += oNode.offsetTop;
        oNode = oNode.offsetParent;
    }
    
    return iTop;
};

/**
 * Handles three keydown events.
 * @scope private
 * @param oEvent The event object for the keydown event.
 */
AutoSuggestControl.prototype.handleKeyDown = function (oEvent /*:Event*/) {

    switch(oEvent.keyCode) {
        case 38: //up arrow
            this.previousSuggestion();
            break;
        case 40: //down arrow 
            this.nextSuggestion();
            break;
        case 13: //enter
            if(this.showingSuggestions != true)
            {
                return true;
            }
            this.hideSuggestions();
            return false;
            break;
    }
    return true;

};

/**
 * Handles keyup events.
 * @scope private
 * @param oEvent The event object for the keyup event.
 */
AutoSuggestControl.prototype.handleKeyUp = function (oEvent /*:Event*/) {

    var iKeyCode = oEvent.keyCode;

    //for backspace (8) and delete (46), shows suggestions without typeahead
    if (iKeyCode == 8 || iKeyCode == 46) {
        this.provider.requestSuggestions(this, false);
        
    //make sure not to interfere with non-character keys
    } else if (iKeyCode < 32 || (iKeyCode >= 33 && iKeyCode < 46) || (iKeyCode >= 112 && iKeyCode <= 123)) {
        //ignore
    } else {
        //request suggestions from the suggestion provider with typeahead
		this.provider.requestSuggestions(this, true);
    }
    return true;
};

/**
 * Hides the suggestion dropdown.
 * @scope private
 */
AutoSuggestControl.prototype.hideSuggestions = function () {

	if (this.layer.childNodes.length > 0) {
	    oTarget = null;
	    for (var i=0; i < this.layer.childNodes.length; i++) {
    	    var oNode = this.layer.childNodes[i];
        	if (oNode.className == "current") {
            	oTarget = oNode;
	            break;
    	    }
    	}
		if (oTarget != null) {
			oThis = this;
			oThis.textbox.value = oTarget.firstChild.nodeValue.replace(/ *(\(.*matches\)) */g, '');

		    if (this.callback_func != null) {			
			  eval(this.callback_func + '(oThis, oTarget);');
			}
		}
	}

    this.layer.style.display = "none";
    this.showingSuggestions = false;
    };


/**
 * Highlights the given node in the suggestions dropdown.
 * @scope private
 * @param oSuggestionNode The node representing a suggestion in the dropdown.
 */
AutoSuggestControl.prototype.highlightSuggestion = function (oSuggestionNode) {
    
    for (var i=0; i < this.layer.childNodes.length; i++) {
        var oNode = this.layer.childNodes[i];
        if (oNode == oSuggestionNode) {
            oNode.className = "current"
        } else if (oNode.className == "current") {
            oNode.className = "";
        }
    }
};

/**
 * Initializes the textbox with event handlers for
 * auto suggest functionality.
 * @scope private
 */
AutoSuggestControl.prototype.init = function () {

    //save a reference to this object
    var oThis = this;
    
    this.showingSuggestions = false;
    //assign the onkeyup event handler
    if (this.textbox == null) {
    	return;
	}
    this.textbox.onkeyup = function (oEvent) {
    
        //check for the proper location of the event object
        if (!oEvent) {
            oEvent = window.event;
        }    
        
        //call the handleKeyUp() method with the event object
        return oThis.handleKeyUp(oEvent);
    };
    
    //assign onkeydown event handler
    this.textbox.onkeydown = function (oEvent) {
    
        //check for the proper location of the event object
        if (!oEvent) {
            oEvent = window.event;
        }    
        
        //call the handleKeyDown() method with the event object
        return oThis.handleKeyDown(oEvent);
    };
    
    //assign onblur event handler (hides suggestions)    
    this.textbox.onblur = function () {
        oThis.hideSuggestions();
    };
    
    //create the suggestions dropdown
    this.createDropDown();
};

/**
 * Highlights the next suggestion in the dropdown and
 * places the suggestion into the textbox.
 * @scope private
 */
AutoSuggestControl.prototype.nextSuggestion = function () {
    var cSuggestionNodes = this.layer.childNodes;

    if (cSuggestionNodes.length > 0 && this.cur < cSuggestionNodes.length-1) {
        var oNode = cSuggestionNodes[++this.cur];
        this.highlightSuggestion(oNode);
        this.textbox.value = oNode.firstChild.nodeValue.replace(/ *(\(.*matches\)) */g, '');
    }
};

/**
 * Highlights the previous suggestion in the dropdown and
 * places the suggestion into the textbox.
 * @scope private
 */
AutoSuggestControl.prototype.previousSuggestion = function () {
    var cSuggestionNodes = this.layer.childNodes;

    if (cSuggestionNodes.length > 0 && this.cur > 0) {
        var oNode = cSuggestionNodes[--this.cur];
        this.highlightSuggestion(oNode);
        this.textbox.value = oNode.firstChild.nodeValue.replace(/ *(\(.*matches\)) */g, '');   
    }
};

/**
 * Selects a range of text in the textbox.
 * @scope public
 * @param iStart The start index (base 0) of the selection.
 * @param iLength The number of characters to select.
 */
AutoSuggestControl.prototype.selectRange = function (iStart /*:int*/, iLength /*:int*/) {

    //use text ranges for Internet Explorer
    if (this.textbox.createTextRange) {
        var oRange = this.textbox.createTextRange(); 
        oRange.moveStart("character", iStart); 
        oRange.moveEnd("character", iLength - this.textbox.value.length);      
        oRange.select();
        
    //use setSelectionRange() for Mozilla
    } else if (this.textbox.setSelectionRange) {
        this.textbox.setSelectionRange(iStart, iLength);
    }     

    //set focus back to the textbox
    this.textbox.focus();      
}; 

/**
 * Builds the suggestion layer contents, moves it into position,
 * and displays the layer.
 * @scope private
 * @param aSuggestions An array of suggestions for the control.
 */
AutoSuggestControl.prototype.showSuggestions = function (aSuggestions /*:Array*/) {
    
    var oDiv = null;
    this.layer.innerHTML = "";  //clear contents of the layer
    this.layer.style.width = this.textbox.offsetWidth;
	for (var i=0; i < aSuggestions.length; i++) {
        oDiv = document.createElement("div");

		oDiv.appendChild(document.createTextNode(aSuggestions[i].value));
//		oDiv.appendChild(document.createAttribute("ID")); // not needed
		oDiv.setAttribute("id", aSuggestions[i].id);
//		oDiv.id = aSuggestions[i].id; // maybe if IE dies, but it seems to accept lowercase id
		this.layer.appendChild(oDiv);
    }
	this.layer.style.left = this.getLeft() + "px";
	this.layer.style.top = (this.getTop()+this.textbox.offsetHeight) + "px";
    this.layer.style.width = this.textbox.offsetWidth;
	this.layer.style.display = "block";
    this.showingSuggestions = true;
};

/**
 * Inserts a suggestion into the textbox, highlighting the 
 * suggested part of the text.
 * @scope private
 * @param sSuggestion The suggestion for the textbox.
 */
AutoSuggestControl.prototype.typeAhead = function (sSuggestion /*:String*/) {

    //check for support of typeahead functionality
    if (this.textbox.createTextRange || this.textbox.setSelectionRange){
        var iLen = this.textbox.value.length; 
        this.textbox.value = sSuggestion; 
        this.selectRange(iLen, sSuggestion.length);
    }
};

// multi select stuff
AutoSuggestControl.prototype.addMulti = function(multi)
{ 
    this.provider.addMulti(this, multi);
}

// clear selected multi items
AutoSuggestControl.prototype.removeSelectedMulti  = function(multi)
{
    var options = new Array();
    for (var i = 0; i < multi.options.length; i++) {
        if (!multi.options[i].selected) {
            options[options.length] = multi.options[i];
        }
    }
    this.removeAllOptions(multi);
    for (var i = 0; i < options.length; i++) {
        if (!optionExists(multi, options[i])) {
            multi.options.length = multi.options.length + 1;
            multi.options[multi.options.length-1].text = options[i].text;
            multi.options[multi.options.length-1].value = options[i].value;
            multi.options[multi.options.length-1].selected = true;
        }
    }
}

AutoSuggestControl.prototype.removeAllOptions = function(field)
{
    if (field.options.length > 0) {
        field.options[0] = null;
        this.removeAllOptions(field);
    }
}
