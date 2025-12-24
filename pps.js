var faformsubmit
var faformsubmit2
var faformtype
var faformname


/* post()
 *
 *  Toggle visibility of a grid of POST parameters.
 */

function post(event) {
    document.querySelector('#ass').style.display = 'none'
    document.querySelector('#posterior').style.display = 'grid'

} // end post()


/* addFeature()
 *
 *  Add a row to the ManageFeatures() form.
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


/* init()
 *
 *  Called when page has loaded.
 */

function init() {
    faformsubmit = document.querySelector('#faformsubmit')
    faformsubmit2 = document.querySelector('#faformsubmit2')

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
    arow = document.querySelector('#arow')
    if(fform = document.querySelector('#addfeature'))
	abutton.addEventListener('click', addFeature)
    
} // end init()
