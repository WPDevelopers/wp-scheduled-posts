export const compareConditionValue = (condition, allFieldsValue) => {
    let flag = true
    if (condition === undefined) return flag
    for (const [key, value] of Object.entries(condition)) {
        if (allFieldsValue[key] === value) {
            flag = false
        } else {
            flag = true
        }
    }
    return flag
}
