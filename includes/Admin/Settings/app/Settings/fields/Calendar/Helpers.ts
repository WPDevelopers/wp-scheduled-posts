import { Option } from "./types";

export const getValues = (options: Option[]) => {
  const values    = options ?? [];
  const allOption = values.find((option) => option.value === "all");
  if(allOption) {
    return [];
  }
  else{
    return values.map(option => option.value)
  }
};