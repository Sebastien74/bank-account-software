/**
 * Social wall
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function (socialWalls) {

    let isDebug = parseInt(document.documentElement.dataset.debug);

    socialWalls.forEach(socialWall => {

        isDebug = typeof socialWall.dataset.debug !== 'undefined' ? parseInt(socialWall.dataset.debug) : isDebug;
        let currentCol = socialWall.closest('.layout-col') ? socialWall.closest('.layout-col') : socialWall.parentNode;
        let previousCol = currentCol ? currentCol.previousElementSibling : null;
        let currentZone = currentCol && currentCol.closest('.layout-zone') ? currentCol.closest('.layout-zone') : (currentCol ? currentCol.parentNode : null);
        let previousZone = currentZone ? currentZone.previousElementSibling : null;
        let zone = previousZone ? previousZone : currentZone;
        let detectElement = previousCol ? previousCol : zone;
        let allowed = isDebug === 0 || isDebug === 1 && parseInt(socialWall.dataset.debug) === 1;

        if (isElementInViewport(detectElement) || isElementInViewport(socialWall)) {
            addScript(socialWall)
        }

        window.addEventListener('scroll', () => {
            if (isElementInViewport(detectElement) || isElementInViewport(socialWall)) {
                addScript(socialWall)
            }
        })

        function addScript(instagramWall) {
            if (allowed && !instagramWall.classList.contains('loaded')) {
                let head = document.head
                /** Create script elem */
                let scriptHead = document.createElement("script")
                scriptHead.type = "text/javascript"
                scriptHead.src = instagramWall.dataset.src
                /** Inject */
                head.append(scriptHead)
                instagramWall.innerHTML = instagramWall.dataset.element
                instagramWall.classList.add('loaded')
            }
        }

        function isElementInViewport(item) {
            let bounding = item.getBoundingClientRect(),
                myElementHeight = item.offsetHeight,
                myElementWidth = item.offsetWidth;
            return bounding.top >= -myElementHeight
                && bounding.left >= -myElementWidth
                && bounding.right <= (window.innerWidth || document.documentElement.clientWidth) + myElementWidth
                && bounding.bottom <= (window.innerHeight || document.documentElement.clientHeight) + myElementHeight;
        }
    });
}