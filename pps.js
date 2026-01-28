/* NAME
 *
 *  pps.js
 *
 * CONCEPT
 *
 *  JavaScript for the Polymorphic Pattern Project.
 */

var faformtype
var faformname
var selecttemplate
var template_id
var metadata
var features
var tsel
var accept
var accepta
var featuresel
var pvid
var penabled // when true, enable display of patterns
var ps


/* contain()
 *
 *  If penabled, handle a click on a pattern title by opening a container
 *  window to display the pattern.
 */

function contain(e) {
    if(!penabled)
        return(false)
    a = e.target
    pid = a.dataset.id
    window.open('viewpattern.php?pid=' + pid + '&pvid=' + pvid.value)
    
} // end contain()


/* enablef()
 *
 *  Called to enable pattern links, which entaials setting penabled true and
 *  making the pattern titles in the list look link-like.
 */

function enablef() {
    if(!penabled) {
        penabled = true
        for(p of ps)
            p.classList.toggle('linklike')
    }
} // end enablef()


/* disablef()
 *
 *  Called to disable pattern links: set penabled false and style the
 *  pattern titles to not look like links.
 */

function disablef() {
    if(penabled) {
        penabled = false
        for(p of ps)
            p.classList.toggle('linklike')
    }
} // end disablef()


/* post()
 *
 *  Toggle visibility of a grid of POST parameters.
 */

function post(event) {
    document.querySelector('#ass').style.display = 'none'
    document.querySelector('#posterior').style.display = 'grid'

} // end post()


/* files()
 *
 *  Toggle visibility of a grid of FILES parameters.
 */

function files(event) {
    document.querySelector('#bum').style.display = 'none'
    document.querySelector('#booty').style.display = 'grid'

} // end files()


/* addFeature()
 *
 *  Add a row to the ManageFeatures() form. NOT CURRENTLY USED.
 */

function addFeature(event) {
    content = ` <div style="grid-column: span 3"></div>
`
    arow.insertAdjacentHTML('beforebegin', content)
    
} /* end addFeature() */


/* faformtypef()
 *
 *  Handle 'input' events on the 'type' popup and 'name' textfield
 *  on the feature add/edit form.
 *
 *  We don't want to accept feature creation or edits if a value for the
 *  type field isn't selected (unless the type form element is disabled,
 *  as it is if there exist values for the feature) or if the 'name'
 *  field doesn't have a value, so we disable the submit buttons in that
 *  event.
 */

function faformtypef() {
    submitState = (faformtype.value != "0" || faformtype.disabled) &&
        faformname.value.length > 0

    accept.disabled = !submitState
    if(accepta)
        accepta.disabled = !submitState
    
} /* end faformtypef() */


/* mfaformf()
 *
 *  Handle 'input' events on the feature-add popup on the template feature add
 *  form.
 *
 *  We don't want to accept template feature additions if a feature hasn't
 *  been selected.
 */

function mfaformf() {
    submitState = featuresel.value != "0"
    accept.disabled = accepta.disabled = !submitState
    
} /* end mfaformf() */


/* stidf()
 *
 *  Handle 'input' events on the select-template popup. We don't want the
 *  submit buttons to be active unless a template has been selected.
 */

function stidf() {
    submitState = (template_id.value != "0")
    if(metadata)
        metadata.disabled = features.disabled = ! submitState
    if(tsel)
        tsel.disabled = ! submitState

} /* end stidf()


/* spidf()
 *
 *  Handle 'input' events on the select-pattern popup. We don't want the
 *  submit button to be active unless a pattern has been selected.
 */

function spidf() {
    submitState = (pattern_id.value != "0")
    if(accept)
        accept.disabled = ! submitState

} /* end spidf()


/* showi()
 *
 *  Find the <DIV> with the ID corresponding to its preview <SPAN> and
 *  make it visible.
 */

function showi(event) {
  ibox = document.querySelector('#i-' + event.target.id.substring(2))
  ibox.style.display = 'block'

} // end showi()


/* hidei()
 *
 *  Find the <DIV> with the ID corresponding to its preview <SPAN> and
 *  hide it.
 */

function hidei(event) {
  ibox = document.querySelector('#i-' + event.target.id.substring(2))
  ibox.style.display = 'none'

} // end hidei()


/* setpv()
 *
 *  Pattern view popup value has changed; enable or disable pattern display.
 */

function setpv(event) {
    svalue = pvid.value // value of <SELECT>, a pattern_view.id value
    if(svalue == 0)
        disablef()
    else
        enablef()
} // end setpv()


/* init()
 *
 *  Called when page has loaded. Primarily, the task is one of assigning
 *  event listeners to elements that are in the page in specific contexts.
 */

function init() {
    selecttemplate = document.querySelector('#selecttemplate')
    selectpattern = document.querySelector('#selpat')
    accept = document.querySelector('#accept')   // submit button 1
    accepta = document.querySelector('#accepta') // submit button 2
    ps = document.querySelectorAll('#ice li a')  // anchors on pattern titles
    if(ps.length)
        for(p of ps)
            p.addEventListener('click', contain)

    if(document.querySelector('#patternform')) {

        // the form for add/edit pattern is on page; manage submit buttons

        rinputs = document.querySelectorAll('.rinput')
        if(rinputs.length) {
            for(rinput of rinputs)
                rinput.addEventListener('input', pf)
            pf()
        }
    }

    if(selectfeature = document.querySelector('#selectfeature'))
        if(feature_id = document.querySelector('#feature_id')) {
            feature_id.addEventListener('input', sf)
            sf()
        }

    if(tmeta = document.querySelector('#tmeta'))
        if(tname = document.querySelector('#name')) {
            tname.addEventListener('input', et)
            et()
        }

    if(selectpattern) {
        if(pattern_id = document.querySelector('#pattern_id')) {
            pattern_id.addEventListener('input', spidf)
            spidf()
        }
    }
    if(selecttemplate) {
        if(template_id = document.querySelector('#template_id'))
            template_id.addEventListener('input', stidf)
        metadata = document.querySelector('#metadata')
        features = document.querySelector('#features')
        tsel = document.querySelector('#tsel')
        if(metadata || features || tsel)
            stidf()
    }

    /* Set and call 'input' event listeners on the 'type' and 'name'
     * fields in the feature add/edit page. */

    if(faformtype = document.querySelector('#faformtype'))
        faformtype.addEventListener('input', faformtypef)
    if(faformname = document.querySelector('#faformname'))
        faformname.addEventListener('input', faformtypef)
    if(faformtype || faformname)
        faformtypef()

    if(featuresel = document.querySelector('#featuresel')) {
        featuresel.addEventListener('input', mfaformf)
        mfaformf()
    }

    if(ass = document.querySelector('#ass'))
        ass.addEventListener('click', post)
    if(bum = document.querySelector('#bum'))
        bum.addEventListener('click', files)
    arow = document.querySelector('#arow')
    if(fform = document.querySelector('#addfeature'))
        abutton.addEventListener('click', addFeature)

    // Set focus and blur event listeners on elements with class 'ilink'.

    for(const ilink of document.querySelectorAll('.ilink')) {
      ilink.addEventListener('mouseenter', showi)
      ilink.addEventListener('mouseleave', hidei)
    }

    /* The pattern view page includes a pattern_view <SELECT> element above
     *  the list of patterns. Listen for 'change' events on that element.
     *  setpv() will set the HREF attributes of <A> elements in the list
     *  to use the selected pattern_view. */

    if(pvid = document.querySelector('#pvid'))
        pvid.addEventListener('change', setpv)
    
} // end init()


/* pf()
 *
 *  Control submit buttons on pattern add/edit form.
 */

function pf(e) {
    rinputs = document.querySelectorAll('.rinput')
    disable = false
    for(rinput of rinputs)
	if(rinput.value.length == 0)
	    disable = true
    accept.disabled = accepta.disabled = disable

} // end pf()


/* sf()
 *
 *  Control whether submit button on select-feature form is active.
 */

function sf(e) {
    feature_id = document.querySelector('#feature_id')
    accept.disabled = (feature_id.value == 0) ? true : false
} // end sf()


/* et()
 *
 *  Control whether submit button on template metadata form is active. It
 *  will be if a non-empty name is present.
 */

function et(e) {
    tname = document.querySelector('#name')
    accepta.disabled = accept.disabled = (tname.value.length > 0) ? false : true
} // end et()
