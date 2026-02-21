// -- Patch idempotent pour forcer le nonce sur <style>/<script> dynamiques
(function (nonce) {

    if (window.__axeptioNoncePatched__) return;
    window.__axeptioNoncePatched__ = true;

    function addNonce(node) {
        if (node && (node.tagName === 'STYLE' || node.tagName === 'SCRIPT') && !node.nonce) {
            try {
                node.setAttribute('nonce', nonce);
            } catch (e) {
            }
        }
    }

    // 1) Patch createElement pour STYLE/SCRIPT
    const origCreate = Document.prototype.createElement;
    Document.prototype.createElement = function (tag, options) {
        const el = origCreate.call(this, tag, options);
        if (typeof tag === 'string' && /^(style|script)$/i.test(tag)) addNonce(el);
        return el;
    };

    // 2) Patch insertions (appendChild / insertBefore)
    const patchInsertion = (proto) => {
        const origAppend = proto.appendChild;
        proto.appendChild = function (node) {
            addNonce(node);
            return origAppend.call(this, node);
        };
        const origInsertBefore = proto.insertBefore;
        proto.insertBefore = function (node, ref) {
            addNonce(node);
            return origInsertBefore.call(this, node, ref);
        };
    };
    patchInsertion(Node.prototype);

    // 3) (sécurité) Observer les ajouts déjà construits via innerHTML
    new MutationObserver(list => {
        for (const m of list) {
            m.addedNodes && m.addedNodes.forEach(addNonce);
        }
    }).observe(document.documentElement, {childList: true, subtree: true});

    // 4) Wrap auto de la fonction globale openAxeptioCookies, qu'elle existe déjà ou plus tard
    const WRAPPED_KEY = '__openAxeptioCookiesWrapped__';

    function wrapOpen(fn) {
        if (typeof fn !== 'function' || fn[WRAPPED_KEY]) return fn;
        const wrapped = function () { /* nonce patches déjà actifs */
            return fn.apply(this, arguments);
        };
        Object.defineProperty(wrapped, WRAPPED_KEY, {value: true});
        return wrapped;
    }

    // si déjà défini, on wrappe
    if (typeof window.openAxeptioCookies === 'function') {
        window.openAxeptioCookies = wrapOpen(window.openAxeptioCookies);
    }

    // si défini **plus tard**, on intercepte l'affectation
    try {
        let _val = window.openAxeptioCookies;
        Object.defineProperty(window, 'openAxeptioCookies', {
            configurable: true,
            enumerable: true,
            get() {
                return _val;
            },
            set(v) {
                _val = wrapOpen(v);
            }
        });
    } catch (e) {
        // certains environnements interdisent redefine: dans ce cas, on fait un fallback par polling
        const t = setInterval(() => {
            if (typeof window.openAxeptioCookies === 'function' && !window.openAxeptioCookies[WRAPPED_KEY]) {
                window.openAxeptioCookies = wrapOpen(window.openAxeptioCookies);
                clearInterval(t);
            }
        }, 50);
        setTimeout(() => clearInterval(t), 5000);
    }

    // 5) (optionnel) Empêcher le href="javascript:..." de court-circuiter la CSP/hashes
    //    On capture le clic et on appelle la fonction wrapée nous-mêmes.
    document.addEventListener('click', function (ev) {
        const a = ev.target.closest('a[href^="javascript:openAxeptioCookies"]');
        if (a && typeof window.openAxeptioCookies === 'function') {
            ev.preventDefault();
            window.openAxeptioCookies(); // patch déjà actif -> styles recevront nonce
        }
    }, {capture: true});

})(document.currentScript.nonce);
