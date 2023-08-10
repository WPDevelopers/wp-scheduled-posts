export const selectStyles = {
    control: (base, state) => ({
      ...base,
      boxShadow: "none", 
      borderColor: "#EBEEF5",
      backgroundColor: "#F9FAFC",
      color: "#6E6E8D",
      "&:hover": {
          borderColor: "#cccccc"
      },
      minWidth: 110,
    }),
    clearIndicator: (base: any) => ({
      ...base,
      display: 'none',
      right: 0,
    }),
    option: (styles, { data, isDisabled, isFocused, isSelected }) => {
      return {
        ...styles,
        backgroundColor: isFocused || isSelected ? '#F3F2FF' : null,
        color: "#000",
        cursor: 'pointer',
      };
    },
    menu: (provided) => ({
      ...provided,
      zIndex: 9999
    })
}