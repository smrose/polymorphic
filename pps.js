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


/* init()
 *
 *  Called when page has loaded.
 */

function init() {
    if(ass = document.querySelector('#ass'))
	ass.addEventListener('click', post)
    arow = document.querySelector('#arow')
    if(fform = document.querySelector('#addfeature'))
	abutton.addEventListener('click', addFeature)
    
} // end init()
