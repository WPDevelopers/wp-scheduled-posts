import React, { useEffect, useState } from "react";
import { FieldArray } from "formik";
import { wpspGetPluginRootURI } from "../../utils/helper";
import Select2 from "react-select";
const Pinterest = ({
  platform,
  fieldName,
  field,
  data,
  boards,
  fetchSectionData,
  noSection,
}) => {
  const boardOptions = boards?.map((board) => {
    return {
      label: board.name || board.id,
      value: board.id,
    };
  });
  const [defaultBoard, setDefaultBoard] = useState();
  const [defaultSection, setDefaultSection] = useState(noSection);
  const [sectionOptions, setSectionOptions] = useState([noSection]);

  useEffect(() => {
    setDefaultSection(noSection);
}, [defaultBoard]);

  useEffect(() => {
    setDefaultBoard(boardOptions?.[0]);
  }, [boards]);

  return (
    <React.Fragment>
      <FieldArray
        name={fieldName}
        render={(arrayHelpers) => (
          <div className="wpsp-modal-social-platform">
            <div className={"entry-head " + platform}>
              <img
                src={
                  wpspGetPluginRootURI +
                  "assets/images/icon-" +
                  platform +
                  "-small-white.png"
                }
                alt="logo"
              />
              <h2 className="entry-head-title">{platform}</h2>
            </div>
            <ul>
              {data.map((item, index) => (
                <li key={index}>
                  <div className="item-content">
                    <div className="entry-thumbnail">
                      <img src={item.thumbnail_url} alt="logo" />
                    </div>
                    <h4 className="entry-title">{item.name}</h4>
                    <div className="control pinterest-select">
                      <Select2
                        value={defaultBoard}
                        // onMenuOpen={() => fetchData()}
                        onChange={setDefaultBoard}
                        options={boardOptions}
                      />
                    </div>
                    <div className="control pinterest-select">
                      <Select2
                        value={defaultSection}
                        onMenuOpen={() =>
                          fetchSectionData(
                            defaultBoard?.value,
                            item,
                            setSectionOptions
                          )
                        }
                        onChange={setDefaultSection}
                        options={sectionOptions}
                      />
                    </div>
                    <div className="control">
                      <input
                        type="checkbox"
                        name={`${field.name}.${index}`}
                        disabled={!boards?.length}
                        onChange={(e) => {
                          if (e.target.checked) {
                            console.log({ default_board_name: defaultBoard });
                            return arrayHelpers.insert(index, {
                              ...item,
                              boards,
                              defaultSection,
                              default_board_name: defaultBoard,
                            });
                          } else {
                            return arrayHelpers.remove(index);
                          }
                        }}
                      />
                      <div></div>
                    </div>
                    {
                      !boards?.length && (
                        <p style={{flex: "1 1 100%", color: "#f00"}}>Please create a board first to publish pin.</p>
                      )
                    }
                  </div>
                </li>
              ))}
            </ul>
          </div>
        )}
      />
    </React.Fragment>
  );
};
export default Pinterest;
