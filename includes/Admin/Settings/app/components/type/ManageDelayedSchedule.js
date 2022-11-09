import React, { useEffect } from "react";
import { useField, useFormikContext } from "formik";

const ManageDelayedSchedule = ({ id, title, desc, ...props }) => {
  const [field] = useField(id);
  const { setFieldValue } = useFormikContext();
  const _value = field.value === undefined ? props?.default : field.value;

  useEffect(() => {
    if (field.value === undefined) {
      setFieldValue(field.name, _value);
    }
  }, []);

  return (
    <div className="form-group wpsp-delayed-schedule-status">
      {console.log(field.value)}
      <div className="form-body">
        <div className="checkbox_wrap">
          <div className="wpsp_switch">
            <input
              type="checkbox"
              checked={_value}
              name={id}
              onChange={() => setFieldValue(field.name, !_value)}
            />
            <span className="wpsp_switch_slider"></span>
          </div>
        </div>
      </div>
      <div className="form-info">
        <h3 className="wpsp-title">
          <label htmlFor={id}>
            {title}
          </label>
        </h3>
        {desc && <span className="desc">{desc}</span>}
      </div>
    </div>
  );
};

export default ManageDelayedSchedule;
