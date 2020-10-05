import React from "react";
import { useField } from "formik";

const Checkbox = ({ id, title, subtitle, desc, setFieldValue }) => {
  const [field] = useField(id);
  return (
    <div className="form-group">
      <div className="form-info">
        <label htmlFor={id}>{title}</label>
        <span className="sub-title">{subtitle}</span>
      </div>
      <div className="form-body">
        <input
          type="checkbox"
          checked={field.value}
          name={field.name}
          onChange={() => setFieldValue(field.name, !field.value)}
        />
        <span className="desc">{desc}</span>
      </div>
    </div>
  );
};

export default Checkbox;
