// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Manage the courses view for the overview block.
 *
 * @module     tool_courserating/rating
 * @copyright  2022 Marina Glancy <marina.glancy@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {get_string as getString} from 'core/str';
import {add as addToast} from 'core/toast';
import ModalForm from "core_form/modalform";
import ModalFactory from "core/modal_factory";
import Fragment from "core/fragment";
import Templates from "core/templates";
import ModalEvents from 'core/modal_events';
import ajax from 'core/ajax';

const SELECTORS = {
    COURSERATING: '.customfield_tool_courserating',
    COURSEWIDGET: '.tool_courserating-widget',
    ADD_RATING: '[data-action=tool_courserating-addrating][data-courseid]',
    VIEW_RATINGS_CFIELD: '.tool_courserating-cfield .tool_courserating-ratings',
    VIEW_RATINGS_LINK: '[data-action="tool_courserating-viewratings"]',
    FLAG_RATING: '[data-action=tool_courserating-toggleflag]',
    DELETE_RATING: `[data-action='tool_courserating-delete-rating']`,
    USER_RATING: `[data-for='tool_courserating-user-rating']`,
    CFIELD_WRAPPER: `[data-for='tool_courserating-cfield-wrapper'][data-courseid]`,
    USER_RATING_FLAG: `[data-for='tool_courserating-user-flag']`,
    RATING_POPUP: `.tool_courserating-reviews-popup`,
    REVIEWS_LIST: `.tool_courserating-reviews-popup [data-for="tool_courserating-reviews"]`,
    SHOWMORE_WRAPPER: `.tool_courserating-reviews-popup [data-for="tool_courserating-reviews"] ` +
        `[data-for="tool_courserating-showmore"]`,
    SHOWMORE_BUTTON: `.tool_courserating-reviews-popup [data-for="tool_courserating-reviews"] ` +
        `[data-for="tool_courserating-showmore"] [data-action="showmore"]`,
    RESET_WITHRATINGS: `.tool_courserating-reviews-popup [data-for="tool_courserating-reviews"] ` +
        `[data-for="tool_courserating-resetwithrating"]`,
    POPUP_SUMMARY: `.tool_courserating-reviews-popup [data-for="tool_courserating-summary"]`,
    SET_WITHRATINGS: `.tool_courserating-reviews-popup [data-for="tool_courserating-summary"] ` +
        `[data-for="tool_courserating_setwithrating"]`,
    RBCELL: `[data-for="tool_courserating-rbcell"][data-ratingid]`,
};

let systemContextId;
let viewRatingsModal;
let addRatingModal;

/**
 * Initialise listeners
 *
 * @param {Number} systemContextIdParam
 * @param {Boolean} useJQuery
 */
export const init = (systemContextIdParam, useJQuery = false) => {
    systemContextId = systemContextIdParam;

    document.addEventListener('click', e => {
        if (!e || !e.target || (typeof e.target.closest === "undefined")) {
            return;
        }

        const addRatingElement = e.target.closest(SELECTORS.ADD_RATING),
            viewRatingsElement = e.target.closest(SELECTORS.VIEW_RATINGS_CFIELD),
            deleteRatingElement = e.target.closest(SELECTORS.DELETE_RATING);

        if (addRatingElement) {
            e.preventDefault();
            const courseid = addRatingElement.getAttribute('data-courseid');
            if (viewRatingsModal) {
                viewRatingsModal.destroy();
            }
            addRating(courseid);
        } else if (viewRatingsElement) {
            e.preventDefault();
            const classes = (' ' + viewRatingsElement.getAttribute('class') + ' '),
                matches = classes.match(/ tool_courserating-ratings-courseid-(\d+) /);
            if (matches) {
                const widget = viewRatingsElement.closest(SELECTORS.COURSEWIDGET);
                if (widget && widget.querySelector(SELECTORS.ADD_RATING)) {
                    addRating(matches[1]);
                } else {
                    viewRatings(matches[1]);
                }
            }
        } else if (deleteRatingElement) {
            e.preventDefault();
            const ratingid = deleteRatingElement.getAttribute('data-ratingid');
            deleteRating(ratingid);
        }
    });

    if (useJQuery) {
        require(['jquery'], function($) {
            $('body').on('updated', '[data-inplaceeditable]', e => reloadFlag(e.target));
        });
    } else {
        document.addEventListener('core/inplace_editable:updated', e => reloadFlag(e.target));
    }
};

/**
 * Update the rating flag fragment
 *
 * @param {Element} inplaceEditable
 */
const reloadFlag = (inplaceEditable) => {
    if (inplaceEditable.dataset.component === 'tool_courserating' && inplaceEditable.dataset.itemtype === 'flag') {
        const ratingid = inplaceEditable.dataset.itemid;
        const node = document.querySelector(`${SELECTORS.USER_RATING_FLAG}[data-ratingid='${ratingid}']`);
        if (node) {
            Fragment.loadFragment('tool_courserating', 'rating_flag', systemContextId, {ratingid}).done((html, js) => {
                Templates.replaceNode(node, html, js);
            });
        }
    }
};

/**
 * Add ratings dialogue
 *
 * @param {Number} courseid
 */
const addRating = (courseid) => {
    addRatingModal = new ModalForm({
        formClass: 'tool_courserating\\form\\addrating',
        args: {courseid},
        modalConfig: {
            title: getString('addrating', 'tool_courserating'),
        },
    });

    // When form is saved, refresh it to remove validation errors, if any:
    addRatingModal.addEventListener(addRatingModal.events.FORM_SUBMITTED, () => {
        getString('changessaved')
            .then(addToast)
            .catch(null);
        refreshRating(courseid);
    });

    addRatingModal.show();
};

/**
 * View ratings dialogue
 *
 * @param {Number} courseid
 */
const viewRatings = (courseid) => {
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
            loadCourseRatingPopupContents({courseid})
            .done(({html, js}) => {
                modal.setBody(html);
                Templates.runTemplateJS(js);
            });
            // Handle hidden event.
            modal.getRoot().on(ModalEvents.hidden, function() {
                // Destroy when hidden.
                modal.destroy();
            });
            modal.show();
            viewRatingsModal = modal;
            return modal;
        })
        .fail(() => null);
};

/**
 * Delete rating with specified id
 *
 * @param {Number} ratingid
 */
const deleteRating = (ratingid) => {
    const form = new ModalForm({
        formClass: 'tool_courserating\\form\\deleterating',
        args: {ratingid},
        modalConfig: {
            title: getString('deleterating', 'tool_courserating'),
        },
    });

    // When form is saved, rating should be deleted.
    form.addEventListener(form.events.FORM_SUBMITTED, async e => {
        const el = document.querySelector(SELECTORS.USER_RATING + `[data-ratingid='${e.detail.ratingid}'`);
        if (el) {
            el.remove();
        }
        refreshRating(e.detail.courseid);
        if (!el) {
            const rbcell = document.querySelector(SELECTORS.RBCELL + `[data-ratingid='${e.detail.ratingid}'`);
            if (rbcell) {
                rbcell.innerHTML = await getString('ratingdeleted', 'tool_courserating');
            }
        }
    });

    form.show();
};

/**
 * Refresh course rating summary
 *
 * @param {Number} courseid
 */
const refreshRating = (courseid) => {
    let el1 = document.getElementsByClassName('tool_courserating-ratings-courseid-' + courseid);
    if (el1 && el1.length) {
        const cfield = el1[0].closest(SELECTORS.COURSERATING);
        Fragment.loadFragment('tool_courserating', 'cfield', systemContextId, {courseid}).done((html, js) => {
            Templates.replaceNode(cfield, html, js);
        });
    }

    const el2 = document.querySelector(SELECTORS.CFIELD_WRAPPER + `[data-courseid='${courseid}']`);
    if (el2) {
        Fragment.loadFragment('tool_courserating', 'cfield', systemContextId, {courseid}).done((html, js) => {
            el2.innerHTML = '';
            Templates.appendNodeContents(el2, html, js);
        });
    }

    const el3 = document.querySelector(`[data-for='tool_courserating-summary'][data-courseid='${courseid}']`);
    if (el3) {
        Fragment.loadFragment('tool_courserating', 'course_ratings_summary', systemContextId, {courseid}).done((html, js) => {
            el3.innerHTML = '';
            Templates.appendNodeContents(el3, html, js);
        });
    }
};

/**
 * Adds or removes CSS class to/from an element
 *
 * @param {Element} ratingFormGroup
 * @param {String} value
 */
const setFormGroupClasses = (ratingFormGroup, value) => {
    const addRemoveClass = (className, add) => {
        if (add && !ratingFormGroup.classList.contains(className)) {
            ratingFormGroup.classList.add(className);
        } else if (!add && ratingFormGroup.classList.contains(className)) {
            ratingFormGroup.classList.remove(className);
        }
    };
    for (let i = 1; i <= 5; i++) {
        addRemoveClass('s-' + i, i <= parseInt(value));
    }
    addRemoveClass('tool_courserating-norating', parseInt(value) === 0);
};

/**
 * Sets up listeneres for the addRating modal form
 *
 * @param {String} grpId
 */
export const setupAddRatingForm = (grpId) => {
    const ratingFormGroup = document.getElementById(grpId);
    const curchecked = ratingFormGroup.querySelector('input:checked');
    setFormGroupClasses(ratingFormGroup, curchecked ? curchecked.value : 0);

    let els = ratingFormGroup.querySelectorAll('input');
    for (let i = 0; i < els.length; i++) {
        els[i].addEventListener('change', e => setFormGroupClasses(ratingFormGroup, e.target.value));
    }

    let labels = ratingFormGroup.querySelectorAll('label');
    for (let i = 0; i < labels.length; i++) {
        labels[i].addEventListener("mouseover", e => {
            const el = e.target.closest('label').querySelector('input');
            setFormGroupClasses(ratingFormGroup, el.value);
        });
        labels[i].addEventListener("mouseleave", () => {
            const el = ratingFormGroup.querySelector('input:checked');
            setFormGroupClasses(ratingFormGroup, el ? el.value : 0);
        });
    }

    const form = ratingFormGroup.closest('form');
    const viewratingsLink = form.querySelector(SELECTORS.VIEW_RATINGS_LINK);
    if (viewratingsLink) {
        viewratingsLink.addEventListener('click', e => {
            e.preventDefault();
            addRatingModal.modal.destroy();
            viewRatings(e.target.dataset.courseid);
        });
    }
};

/**
 * Sets up the "View course ratings" popup
 */
export const setupViewRatingsPopup = () => {
    const el = document.querySelector(SELECTORS.REVIEWS_LIST);
    const reloadReviews = (offset = 0) => {
        const params = {
            courseid: el.dataset.courseid,
            offset,
            withrating: el.dataset.withrating
        };
        return Fragment.loadFragment('tool_courserating', 'course_reviews', el.dataset.systemcontextid, params);
    };

    el.addEventListener('click', e => {
        const button = e.target.closest(SELECTORS.SHOWMORE_BUTTON);
        if (button) {
            const wrapper = button.closest(SELECTORS.SHOWMORE_WRAPPER);
            e.preventDefault();
            reloadReviews(button.dataset.nextoffset).done((html, js) => Templates.replaceNode(wrapper, html, js));
        }
        const resetLink = e.target.closest(SELECTORS.RESET_WITHRATINGS);
        if (resetLink) {
            e.preventDefault();
            el.dataset.withrating = 0;
            reloadReviews(0).done((html, js) => Templates.replaceNodeContents(el, html, js));
        }
    });

    const elSummary = document.querySelector(SELECTORS.POPUP_SUMMARY);
    elSummary.addEventListener('click', e => {
        const withRatingButton = e.target.closest(SELECTORS.SET_WITHRATINGS);
        if (withRatingButton) {
            el.dataset.withrating = (el.dataset.withrating === withRatingButton.dataset.withrating) ?
                0 : withRatingButton.dataset.withrating;
            reloadReviews(0).done((html, js) => Templates.replaceNodeContents(el, html, js));
        }
    });
};

/**
 * Hide the custom field editor on the course edit page
 *
 * @param {String} fieldname
 */
export const hideEditField = (fieldname) => {
    const s = '#fitem_id_customfield_' + fieldname;
    let el = document.querySelector(s + '_editor');
    if (el) {
        el.style.display = 'none';
        el = document.querySelector(s + '_static');
        if (el) {
            el.style.display = 'none';
        }
    }
};


/**
 * Loads Course Rating popup contents. Allows both loggedin and nonloggedin requests.
 *
 * @param {object} args Parameters for the callback.
 * @return {Promise} JQuery promise object resolved when the fragment has been loaded.
 */
const loadCourseRatingPopupContents = function(args) {
    const isloggedin = !document.body.classList.contains('notloggedin');

    if (isloggedin) {
        return Fragment.loadFragment('tool_courserating', 'course_ratings_popup', systemContextId, args)
            .then((html, js) => ({html, js}));
    }

    return ajax.call([{
        methodname: 'tool_courserating_course_rating_popup',
        args
    }], undefined, false)[0]
    .then(function(data) {
        return {html: data.html, js: Fragment.processCollectedJavascript(data.javascript)};
    });
};
