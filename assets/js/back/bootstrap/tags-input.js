import 'use-bootstrap-tag/dist/use-bootstrap-tag.css';
import UseBootstrapTag from 'use-bootstrap-tag';

/**
 * Tag inputs.
 *
 * https://github.com/use-bootstrap/use-bootstrap-tag
 */
export default function () {
    document.querySelectorAll('[data-role="tags-input"]').forEach(tag => {
        UseBootstrapTag(tag);
    });
}
