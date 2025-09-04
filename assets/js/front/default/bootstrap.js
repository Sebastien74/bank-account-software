/**
 * Bootstrap
 *
 * @copyright 2024
 * @author Sébastien FOURNIER <contact@sebastien-fournier.com>
 * @licence under the MIT License (LICENSE.txt)
 */

function adjustColumnsByMargin() {
    document.querySelectorAll(".layout-block, .layout-col").forEach(col => {
        let style = window.getComputedStyle(col);
        // 1️⃣ Remove previously added max-width to reset to the original CSS
        col.style.maxWidth = "";
        // 2️⃣ Get the width defined in % from the original CSS
        let widthValue = col.style.width || style.getPropertyValue("width");
        let widthPercent = widthValue.includes("%")
            ? parseFloat(widthValue)
            : (parseFloat(style.width) / col.parentElement.clientWidth) * 100;
        // 3️⃣ Check if there is a margin
        let marginRight = parseFloat(style.marginRight) || 0;
        let marginLeft = parseFloat(style.marginLeft) || 0;
        // 4️⃣ Apply calculation ONLY if at least one margin is greater than 0
        if (marginRight > 0 || marginLeft > 0) {
            let parentWidth = col.parentElement.clientWidth || 1; // Get parent width in px
            // 5️⃣ Convert margins from px to % of the parent width
            let totalMarginPercent = ((marginRight + marginLeft) / parentWidth) * 100;
            // 6️⃣ Calculate the new adjusted width (always ≤ original width)
            let newWidthPercent = Math.max(0, widthPercent - totalMarginPercent);
            // 7️⃣ Apply the new max-width
            col.style.maxWidth = `${newWidthPercent}%`;
        }
    });
}

// Prevent unnecessary recalculations on Y-axis resize
let lastWindowWidth = window.innerWidth;

function handleResize() {
    let currentWindowWidth = window.innerWidth;

    if (currentWindowWidth !== lastWindowWidth) {
        adjustColumnsByMargin();
        lastWindowWidth = currentWindowWidth;
    }
}

// Run at load and on X-axis resize only
window.addEventListener("load", adjustColumnsByMargin);
window.addEventListener("resize", handleResize);

document.addEventListener('DOMContentLoaded', function () {

    const tab = document.querySelector('.nav-tabs');
    const pill = document.querySelector('.nav-pills');
    if (tab || pill) {
        import('../bootstrap/dist/tab').then(({ default: Tab }) => {
            document.querySelectorAll('.nav-tabs, .nav-pills').forEach(tabToggleEl => {
                tabToggleEl.querySelectorAll('button').forEach(triggerEl => {
                    const tabTrigger = new Tab(triggerEl);
                    triggerEl.addEventListener('click', event => {
                        event.preventDefault();
                        tabTrigger.show();
                    });
                });
            });
        }).catch(error => console.error(error.message));
    }

    const dropdown = document.querySelector('.dropdown-toggle');
    if (dropdown) {
        import('../bootstrap/dist/dropdown').then(({default: Dropdown}) => {
            const dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
            const dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
                if (!dropdownToggleEl.classList.contains('loaded')) {
                    dropdownToggleEl.classList.add('loaded')
                    return new Dropdown(dropdownToggleEl);
                }
            });
        }).catch(error => console.error(error.message));
    }

    const collapse = document.querySelector('.collapse');
    if (collapse) {
        import('../bootstrap/dist/collapse').then(({default: Collapse}) => {
            document.querySelectorAll('.collapse').forEach(function (collapseToggleEl) {
                if (!collapseToggleEl.classList.contains('loaded')) {
                    collapseToggleEl.classList.add('loaded')
                    const bsCollapse = new Collapse(collapseToggleEl, {
                        toggle: false
                    });
                }
                // collapseToggleEl.addEventListener('show.bs.collapse', event => {
                //     let parent = event.target.parentNode;
                //     parent.querySelectorAll('.hide-on-collapse').forEach(function (hideEl) {
                //         hideEl.classList.add('d-none');
                //     });
                // });
                // collapseToggleEl.addEventListener('hide.bs.collapse', event => {
                //     let parent = event.target.parentNode;
                //     parent.querySelectorAll('.hide-on-collapse').forEach(function (hideEl) {
                //         hideEl.classList.remove('d-none');
                //     });
                // });
            });
        }).catch(error => console.error(error.message));
    }

    const navigation = document.querySelector('.menu-container');
    if (navigation) {
        import('../bootstrap/modules/navigation').then(({default: Nav}) => {
            new Nav();
        }).catch(error => console.error(error.message));
    }

    const carousel = document.querySelector('.carousel');
    if (carousel) {
        import('../bootstrap/modules/carousel').then(({default: Carousel}) => {
            new Carousel();
        }).catch(error => console.error(error.message));
    }

    const modal = document.querySelector('.modal');
    if (modal) {
        import('../bootstrap/modules/modal').then(({default: Modal}) => {
            new Modal();
        }).catch(error => console.error(error.message));
    }

    const toast = document.querySelector('.toast');
    if (toast) {
        import('../bootstrap/modules/toast').then(({default: Toast}) => {
            new Toast();
        }).catch(error => console.error(error.message));
    }

    const tooltip = document.querySelector('[data-bs-toggle="tooltip"]');
    if (tooltip) {
        import('../bootstrap/modules/tooltip').then(({default: Tooltip}) => {
            new Tooltip();
        }).catch(error => console.error(error.message));
    }
});