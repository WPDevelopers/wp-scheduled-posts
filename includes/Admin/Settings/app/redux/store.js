import { createStore, applyMiddleware } from 'redux'
import { composeWithDevTools } from 'redux-devtools-extension'
import logger from 'redux-logger'
import thunk from 'redux-thunk'

import rootReducer from './reducers'

let middleware = [thunk]
console.log(process.env.NODE_ENV)
if (process.env.NODE_ENV !== 'production') {
    middleware = [...middleware, logger]
}

export default createStore(
    rootReducer,
    {},
    composeWithDevTools(applyMiddleware(...middleware))
)
