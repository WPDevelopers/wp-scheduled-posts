/******/ (function() { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./assets/elementor/ModalButton.js":
/*!*****************************************!*\
  !*** ./assets/elementor/ModalButton.js ***!
  \*****************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_1__);


const {
  __
} = wp.i18n;
const ModalButton = props => {
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    id: "elementor-panel-footer-wpsp-modal-label"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    xmlns: "http://www.w3.org/2000/svg",
    x: "0px",
    y: "0px",
    viewBox: "0 0 500 500",
    style: "enable-background:new 0 0 500 500;display:block;width:13px;margin:0 auto;"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("style", {
    type: "text/css"
  }, ".st0", "{fill:#A4AFB7;}", "#elementor-panel-footer-wpsp-modal:hover .st0", "{fill:#d5dadf;}"), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("g", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("g", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    class: "st0",
    d: "M212.3,462.4C95,462.4-0.4,366.9-0.4,249.7S95,37,212.3,37c37,0,73.2,9.6,105.1,27.9 c9.8,5.7,13.2,18.1,7.5,27.7c-5.7,9.8-18.1,13.2-27.7,7.5c-25.6-14.7-55.1-22.5-84.9-22.5c-94.7,0-171.8,77.1-171.8,171.8 s77.1,171.8,171.8,171.8c48.1,0,92.6-19.4,125.5-54.3c7.8-8.3,20.7-8.5,28.7-1c8.3,7.8,8.5,20.7,1,28.7 C327.4,437.8,271,462.4,212.3,462.4z"
  })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    class: "st0",
    d: "M186.1,208.3l-43.2-39.3c-8.3-7.5-21.2-7-28.7,1.3c-7.5,8.3-7,21.2,1.3,28.7l46.8,42.4 C165.9,227.7,174.5,215.8,186.1,208.3z"
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    class: "st0",
    d: "M445.4,81.7c-7-8.8-19.9-10.4-28.7-3.4L250,210.1c11.1,8.3,19.1,20.4,21.7,34.7L442,110.2 C451.1,103.2,452.4,90.5,445.4,81.7z"
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    class: "st0",
    d: "M234.3,222.8c-5.2-2.8-11.1-4.4-17.3-4.4c-5.7,0-10.9,1.3-15.5,3.4c-12.7,6-21.2,18.6-21.2,33.4 c0,0.8,0,1.6,0,2.3c1.3,19.1,17.1,34.4,36.7,34.4c18.9,0,34.4-14.2,36.5-32.6c0.3-1.3,0.3-2.8,0.3-4.4 C253.7,241.1,245.9,229,234.3,222.8z"
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    class: "st0",
    d: "M493.8,202.6h-51.2c-3.4,0-6.2,2.8-6.2,6.2v45.5c0,3.4,2.8,6.2,6.2,6.2h51.2c3.4,0,6.2-2.8,6.2-6.2v-45.5 C500,205.4,497.2,202.6,493.8,202.6z"
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("g", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    class: "st0",
    d: "M410,202.6h-51.2c-3.4,0-6.2,2.8-6.2,6.2v45.5c0,3.4,2.8,6.2,6.2,6.2H410c3.4,0,6.2-2.8,6.2-6.2v-45.5 C416.4,205.4,413.6,202.6,410,202.6z"
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    class: "st0",
    d: "M410,277.6h-51.2c-3.4,0-6.2,2.8-6.2,6.2v45.5c0,3.4,2.8,6.2,6.2,6.2H410c3.4,0,6.2-2.8,6.2-6.2v-45.5 C416.4,280.2,413.6,277.6,410,277.6z"
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    class: "st0",
    d: "M493.8,277.6h-51.2c-3.4,0-6.2,2.8-6.2,6.2v45.5c0,3.4,2.8,6.2,6.2,6.2h51.2c3.4,0,6.2-2.8,6.2-6.2v-45.5 C500,280.2,497.2,277.6,493.8,277.6z"
  })))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    class: "elementor-screen-only"
  }, __('SchedulePress', 'wp-scheduled-posts')));
};
/* harmony default export */ __webpack_exports__["default"] = (ModalButton);

/***/ }),

/***/ "./assets/elementor/after.js":
/*!***********************************!*\
  !*** ./assets/elementor/after.js ***!
  \***********************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "KitAfterSave": function() { return /* binding */ KitAfterSave; }
/* harmony export */ });
class KitAfterSave extends $e.modules.hookData.After {
  register() {
    $e.hooks.registerDataAfter(this);
  }
  getCommand() {
    return 'document/save/save';
  }
  getConditions(args) {
    const {
      status,
      document = elementor.documents.getCurrent()
    } = args;
    return 'publish' === status;
  }
  getId() {
    return 'wpsp-after-save';
  }
  apply(args) {
    // On save clear cache of all edited documents and dynamic tags.
    // This is needed because when returning to the editor after saving the kit, it was still displaying the old data.
    this.clearDocumentCache();
    this.clearDynamicTagsCache();
    console.log('after ', args);
    if ('publish' === args.status) {
      elementor.notifications.showToast({
        message: __('Your changes have been updated.', 'elementor'),
        buttons: [{
          name: 'back_to_editor',
          text: __('Back to Editor', 'elementor'),
          callback() {
            $e.run('panel/global/close');
          }
        }]
      });
    }
    if (elementor.activeBreakpointsUpdated) {
      const reloadConfirm = elementorCommon.dialogsManager.createWidget('alert', {
        id: 'elementor-save-kit-refresh-page',
        headerMessage: __('Reload Elementor Editor', 'elementor'),
        message: __('You have made modifications to the list of Active Breakpoints. For these changes to take effect, you need to reload Elementor Editor.', 'elementor'),
        position: {
          my: 'center center',
          at: 'center center'
        },
        strings: {
          confirm: __('Reload Now', 'elementor')
        },
        onConfirm: () => location.reload()
      });
      reloadConfirm.show();
    }
  }
  clearDocumentCache() {
    Object.keys(elementor.documents.documents).forEach(id => {
      elementor.documents.invalidateCache(id);
    });
  }
  clearDynamicTagsCache() {
    elementor.dynamicTags.cleanCache();
    elementor.dynamicTags.loadCacheRequests();
  }
}
/* harmony default export */ __webpack_exports__["default"] = (KitAfterSave);

/***/ }),

/***/ "./assets/elementor/component.js":
/*!***************************************!*\
  !*** ./assets/elementor/component.js ***!
  \***************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _after__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./after */ "./assets/elementor/after.js");
// import * as hooks from './hooks';
// import * as commands from './commands/';
// import Repeater from './repeater';

/* harmony default export */ __webpack_exports__["default"] = (class extends $e.modules.ComponentBase {
  pages = {};
  __construct(args) {
    super.__construct(args);
    elementor.on('panel:init', e => {
      console.log('panel:init', args);
      // args.manager.addPanelPages();

      args.manager.addPanelMenuItem();
    });

    // elementor.hooks.addFilter( 'panel/header/behaviors', args.manager.addHeaderBehavior );

    // elementor.addControlView( 'global-style-repeater', Repeater );
  }

  getNamespace() {
    return 'wpsp/schedule';
  }
  defaultRoutes() {
    return {
      menu: () => {
        elementor.getPanelView().setPage('kit_menu');
      }
    };
  }

  // defaultCommands() {
  // 	return this.importCommands( commands );
  // }

  defaultShortcuts() {
    return {
      open: {
        keys: 'ctrl+k',
        dependency: () => {
          return 'kit' !== elementor.documents.getCurrent().config.type;
        }
      },
      back: {
        keys: 'esc',
        scopes: ['panel'],
        dependency: () => {
          return elementor.documents.isCurrent(elementor.config.kit_id) && !jQuery('.dialog-widget:visible').length;
        }
      }
    };
  }
  defaultHooks() {
    return this.importHooks({
      after: _after__WEBPACK_IMPORTED_MODULE_0__["default"]
    });
  }
  renderTab(tab) {
    console.log('tab', tab);
    elementor.getPanelView().setPage('kit_settings').content.currentView.activateTab(tab);
  }
});

/***/ }),

/***/ "react":
/*!************************!*\
  !*** external "React" ***!
  \************************/
/***/ (function(module) {

module.exports = window["React"];

/***/ }),

/***/ "react-dom":
/*!***************************!*\
  !*** external "ReactDOM" ***!
  \***************************/
/***/ (function(module) {

module.exports = window["ReactDOM"];

/***/ }),

/***/ "@wordpress/element":
/*!*********************************!*\
  !*** external ["wp","element"] ***!
  \*********************************/
/***/ (function(module) {

module.exports = window["wp"]["element"];

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	!function() {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = function(module) {
/******/ 			var getter = module && module.__esModule ?
/******/ 				function() { return module['default']; } :
/******/ 				function() { return module; };
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	!function() {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = function(exports, definition) {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	!function() {
/******/ 		__webpack_require__.o = function(obj, prop) { return Object.prototype.hasOwnProperty.call(obj, prop); }
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	!function() {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = function(exports) {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	}();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry need to be wrapped in an IIFE because it need to be isolated against other modules in the chunk.
!function() {
/*!***********************************!*\
  !*** ./assets/elementor/index.js ***!
  \***********************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var react_dom__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react-dom */ "react-dom");
/* harmony import */ var react_dom__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react_dom__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _ModalButton__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./ModalButton */ "./assets/elementor/ModalButton.js");
const {
  __
} = wp.i18n;



// import "./elementor-editor.js";
// import component from "./component.js";

jQuery(window).on('elementor:loaded', () => {
  const component = (__webpack_require__(/*! ./component.js */ "./assets/elementor/component.js")["default"]);
  const modal = jQuery('#schedulepress-elementor-modal');
  const openModal = e => {
    e.preventDefault();
    modal.fadeIn();
  };
  const closeModal = function (e) {
    e.preventDefault();
    if (e.target === this) {
      modal.fadeOut();
    }
  };
  const Component = new component({
    manager: {
      addPanelMenuItem: () => {
        let xDiv = document.createElement('div');
        xDiv.id = 'elementor-panel-footer-wpsp-modal';
        xDiv.classList.add('elementor-panel-footer-tool');
        xDiv.classList.add('tooltip-target');
        xDiv.setAttribute('data-tooltip', __('SchedulePress', 'wp-scheduled-posts'));
        document.getElementById('elementor-panel-footer-tools').insertBefore(xDiv, document.getElementById('elementor-panel-footer-saver-publish'));

        // ReactDOM.render(
        //     <ModalButton config={notificationX} />,
        //     xDiv
        // );

        return;

        // elementor.panel.currentView.footer.currentView.ui.menuButtons.find('#elementor-panel-footer-saver-preview');
        jQuery('#elementor-panel-footer-wpsp-modal').insertAfter('#elementor-panel-footer-saver-preview');
        jQuery('body').on('click', '#elementor-panel-footer-wpsp-modal', openModal);
        jQuery('body').on('click', '.elementor-templates-modal__header__close > svg, .elementor-templates-modal__header__close > svg *, #schedulepress-elementor-modal', closeModal);
        elementor.panel.currentView.footer.currentView.addSubMenuItem('saver-options', {
          name: 'wpsp-schedule-button',
          icon: 'eicon-plus-square',
          title: 'Schedule button',
          description: 'Schedule button',
          callback: openModal
          // before: 'save-template',
        });
      }
    }
  });

  $e.components.register(Component);
});
}();
/******/ })()
;
//# sourceMappingURL=elementor-editor.js.map