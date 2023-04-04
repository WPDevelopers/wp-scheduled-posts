const { __ } = wp.i18n;
import React from "react";
import ReactDOM from "react-dom";
import ModalButton from "./ModalButton";
// import "./elementor-editor.js";
// import component from "./component.js";


jQuery( window ).on( 'elementor:loaded', () => {
    const component = require("./component.js").default;
    const modal = jQuery('#schedulepress-elementor-modal');
    const openModal = (e)=> {
        e.preventDefault();
        modal.fadeIn();
    }
    const closeModal = function(e){
        e.preventDefault();
        if (e.target === this) {
            modal.fadeOut();
        }
    }

    const Component = new component({
        manager: {
            addPanelMenuItem: ()=> {


                let xDiv = document.createElement('div');
                xDiv.id = 'elementor-panel-footer-wpsp-modal';
                xDiv.classList.add('elementor-panel-footer-tool');
                xDiv.classList.add('tooltip-target');
                xDiv.setAttribute('data-tooltip', __( 'SchedulePress', 'wp-scheduled-posts' ));

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
                    callback: openModal,
                    // before: 'save-template',
                });
            }
        }
    });

    $e.components.register( Component );


});
