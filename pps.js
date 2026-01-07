var faformsubmit
var faformsubmit2
var faformtype
var faformname
var selecttemplate
var template_id
var metadata
var features
var tsel


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

    faformsubmit.disabled = !submitState
    if(faformsubmit2)
	faformsubmit2.disabled = !submitState
    
} /* end faformtypef() */


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


/* init()
 *
 *  Called when page has loaded.
 */

function init() {
    faformsubmit = document.querySelector('#faformsubmit')
    faformsubmit2 = document.querySelector('#faformsubmit2')
    selecttemplate = document.querySelector('#selecttemplate')

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
    
} // end init()
