import React from "react";
import { useField } from "formik";

const Radio = props => {
  const [field] = useField(props.id);
  return (
    <div className="form-group">
      <div className="form-info">
        <label htmlFor={props.id}>{props.title}</label>
        <span className="sub-title">{props.subtitle}</span>
      </div>
      <div className="form-body">
        {Object.keys(props.options).map((item, index) => (
          <div className="radio-item" key={index}>
            <input
              id={props.id + item}
              value={item}
              checked={field.value == item}
              name={props.id}
              type="radio"
              onChange={() => props.setFieldValue(field.name, item)}
            />
            <label htmlFor={props.id + item}>{props.options[item]}</label>
          </div>
        ))}
        <span className="desc">{props.desc}</span>
      </div>
    </div>
  );
};

export default Radio;
