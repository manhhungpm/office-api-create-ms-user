import store from '~/store'

export default async (to, from, next) => {
  if (!store.getters['auth/check']) {
      next({ path: "/create-ms-user" })
  } else {
    next()
  }
}
