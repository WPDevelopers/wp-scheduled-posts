import React from "react";
import { __ } from "@wordpress/i18n";
const Screenshot = ({ id, title, src, link }) => {
  return (
    <div id={id} className="wpsp-pro-disabled-badge">
      <img title={title} src={src} />
      <a href={link} target="_blank" className="wpsp-get-pro-button">
        Get PRO to unlock
      </a>
    </div>
  );
};

export default Screenshot;
