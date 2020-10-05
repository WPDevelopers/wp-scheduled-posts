export const compareConditionValue = (condition, allFieldsValue) => {
    let flag = false
    if (condition === undefined) return flag
    for (const [key, value] of Object.entries(condition)) {
        if (allFieldsValue[key] === value) {
            flag = true
        } else {
            flag = false
        }
    }
    return flag
}
