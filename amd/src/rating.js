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
 * @copyright  2022 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {get_string as getString} from 'core/str';
import {add as addToast} from 'core/toast';
import ModalForm from "core_form/modalform";
import ModalFactory from "core/modal_factory";
import Fragment from "core/fragment";
import Templates from "core/templates";

const SELECTORS = {
    COURSERATING: '.customfield_tool_courserating',
    ADD_RATING: '[data-action=tool_courserating-addrating][data-courseid]',
    VIEW_RATINGS: '.tool_courserating-cfield .tool_courserating-ratings',
    FLAG_RATING: '[data-action=tool_courserating-toggleflag]',
    DELETE_RATING: `[data-action='tool_courserating-delete-rating']`,
    USER_RATING: `[data-for='tool_courserating-user-rating']`,
    CFIELD_WRAPPER: `[data-for='tool_courserating-cfield-wrapper'][data-courseid]`,
};

let systemContextId;

export const init = (systemContextIdParam) => {
    systemContextId = systemContextIdParam;

    document.addEventListener('click', e => {
        const addRatingElement = e.target.closest(SELECTORS.ADD_RATING),
            viewRatingsElement = e.target.closest(SELECTORS.VIEW_RATINGS),
            deleteRatingElement = e.target.closest(SELECTORS.DELETE_RATING);

        if (addRatingElement) {
            e.preventDefault();
            const courseid = addRatingElement.getAttribute('data-courseid');
            addRating(courseid);
        } else if (viewRatingsElement) {
            e.preventDefault();
            const classes = (' ' + viewRatingsElement.getAttribute('class') + ' '),
                matches = classes.match(/ tool_courserating-ratings-courseid-(\d+) /);
            if (matches) {
                viewRatings(matches[1]);
            }
        } else if (deleteRatingElement) {
            e.preventDefault();
            const ratingid = deleteRatingElement.getAttribute('data-ratingid');
            deleteRating(ratingid);
        }
    });

    document.addEventListener('core/inplace_editable:updated', e => {
        const inplaceEditable = e.target;
        if (inplaceEditable.dataset.component === 'tool_courserating' && inplaceEditable.dataset.itemtype === 'flag') {
            const ratingid = inplaceEditable.dataset.itemid;
            const node = document.querySelector(`[data-for='tool_courserating-user-flag'][data-ratingid='${ratingid}']`);
            if (node) {
                Fragment.loadFragment('tool_courserating', 'rating_flag', systemContextId, {ratingid}).done((html, js) => {
                    Templates.replaceNode(node, html, js);
                });
            }
        }
    });
};

/**
 * Add ratings dialogue
 *
 * @param {Number} courseid
 */
const addRating = (courseid) => {
    const form = new ModalForm({
        formClass: 'tool_courserating\\form\\addrating',
        args: {courseid},
        modalConfig: {
            title: getString('addrating', 'tool_courserating'),
        },
    });

    // When form is saved, refresh it to remove validation errors, if any:
    form.addEventListener(form.events.FORM_SUBMITTED, () => {
        getString('changessaved')
            .then(addToast)
            .catch(null);
        refreshRating(courseid);
    });

    form.show();
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
            Fragment.loadFragment('tool_courserating', 'course_ratings_popup', systemContextId, {courseid}).done((html, js) => {
                modal.setBody(html);
                Templates.runTemplateJS(js);
            });
            modal.show();
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
    form.addEventListener(form.events.FORM_SUBMITTED, (e) => {
        const el = document.querySelector(SELECTORS.USER_RATING + `[data-ratingid='${e.detail.ratingid}'`);
        if (el) {
            el.remove();
        }
        refreshRating(e.detail.courseid);
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
