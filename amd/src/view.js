import {get_string as getString} from 'core/str';
import {add as addToast} from 'core/toast';
import ModalForm from "core_form/modalform";
import ModalFactory from "core/modal_factory";
import Fragment from "core/fragment";
import Templates from "core/templates";

const SELECTORS = {
    COURSERATING: '.customfield_tool_courserating',
    ADD_RATING: '[data-action=tool_courserating-addrating][data-ctxid]',
    VIEW_RATINGS: '.tool_courserating-cfield .tool_courserating-ratings',
    FLAG_RATING: '[data-action=tool_courserating-toggleflag]',
    DELETE_REVIEW: `[data-action='tool_courserating-delete-review']`,
    USER_RATING: `[data-for='tool_courserating-user-rating']`,
    CFIELD_WRAPPER: `[data-for='tool_courserating-cfield-wrapper'][data-ctxid]`,
};

export const init = (courseid) => {
    /* eslint-disable no-console */
    console.log("tool_courserating init "+courseid);

    document.addEventListener('click', e => {
        const addRatingElement = e.target.closest(SELECTORS.ADD_RATING),
            viewRatingsElement = e.target.closest(SELECTORS.VIEW_RATINGS),
            deleteRatingElement = e.target.closest(SELECTORS.DELETE_REVIEW);

        if (addRatingElement) {
            e.preventDefault();
            const ctxid = addRatingElement.getAttribute('data-ctxid');
            addRating(ctxid);
        } else if (viewRatingsElement) {
            e.preventDefault();
            const classes = (' ' + viewRatingsElement.getAttribute('class') + ' '),
                matches = classes.match(/ tool_courserating-ratings-ctx-(\d+) /);
            if (matches) {
                viewRatings(matches[1]);
            }
        } else if (deleteRatingElement) {
            e.preventDefault();
            const id = deleteRatingElement.getAttribute('data-id');
            deleteRating(id);
        }
    });
};

const addRating = (ctxid) => {
    const form = new ModalForm({
        formClass: 'tool_courserating\\form\\addrating',
        args: { ctxid },
        modalConfig: {
            title: getString('addrating', 'tool_courserating'),
        },
    });

    // When form is saved, refresh it to remove validation errors, if any:
    form.addEventListener(form.events.FORM_SUBMITTED, () => {
        getString('changessaved')
            .then(addToast)
            .catch(null);
        refreshRating(0, ctxid);
    });

    form.show();

    // Reload the page on cancel.
    //form.addEventListener(form.events.CANCEL_BUTTON_PRESSED, () => window.location.reload());

};

const viewRatings = (ctxid) => {
    ModalFactory.create({
        type: ModalFactory.types.CANCEL,
        title: getString('coursereviews', 'tool_courserating'),
        large: true,
        buttons: {
            cancel: getString('closebuttontitle', 'core'),
        },
        removeOnClose: true,
    })
        .then(modal => {
            modal.setLarge();
            Fragment.loadFragment('tool_courserating', 'reviews', ctxid, []).done((html, js) => {
                modal.setBody(html);
                Templates.runTemplateJS(js);
            });
            modal.show();
            return modal;
        });
};

const deleteRating = (id) => {
    const form = new ModalForm({
        formClass: 'tool_courserating\\form\\deleterating',
        args: { id },
        modalConfig: {
            title: getString('deleterating', 'tool_courserating'),
        },
    });

    // When form is saved, rating should be deleted.
    form.addEventListener(form.events.FORM_SUBMITTED, () => {
        const el = document.querySelector(SELECTORS.USER_RATING + `[data-id='${id}'`);
        if (el) {
            el.remove();
        }
    });

    form.show();
};

const refreshRating = (id, ctxid = 0) => {
    console.log('_refreshrating id = '+id+', ctxid = '+ctxid);
    if (ctxid > 0) {
        const el = document.getElementsByClassName('tool_courserating-ratings-ctx-'+ctxid);
        if (el && el.length) {
            const cfield = el[0].closest(SELECTORS.COURSERATING);
            Fragment.loadFragment('tool_courserating', 'cfield', ctxid, []).done((html, js) => {
                Templates.replaceNode(cfield, html, js);
            });
            return;
        }
    }
    const selector = (id > 0) ? `[data-id='${id}']` : `[data-ctxid='${ctxid}']`;
    const el = document.querySelector(SELECTORS.CFIELD_WRAPPER + selector);
    if (el) {
        Fragment.loadFragment('tool_courserating', 'cfield', ctxid, []).done((html, js) => {
            el.innerHTML = '';
            Templates.appendNodeContents(el, html, js);
        });
    }
};
