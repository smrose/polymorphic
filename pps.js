/* NAME
 *
 *  pps.js
 *
 * CONCEPT
 *
 *  JavaScript for the Polymorphic Pattern Project.
 */


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
 *  Called when page has loaded. Primarily, the task is one of assigning
 *  event listeners to elements that are in the page in specific contexts.
 */

function init() {
    if(ass = document.querySelector('#ass'))
        ass.addEventListener('click', post)
    if(bum = document.querySelector('#bum'))
        bum.addEventListener('click', files)

    // Set focus and blur event listeners on elements with class 'ilink'.

    for(const ilink of document.querySelectorAll('.ilink')) {
      ilink.addEventListener('mouseenter', showi)
      ilink.addEventListener('mouseleave', hidei)
    }

} // end init()
