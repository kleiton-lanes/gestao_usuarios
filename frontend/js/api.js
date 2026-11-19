/**
 * COMUNICAÇÃO COM A API
 *
 * Este é o ÚNICO arquivo do frontend que sabe o endereço da API e usa fetch.
 * O frontend nunca acessa o banco de dados: tudo passa por aqui.
 */
const API_URL = 'http://localhost:3000/users';

async function request(url, options) {
  const config = options || {};
  config.headers = { 'Content-Type': 'application/json' };

  let response;
  try {
    response = await fetch(url, config);
  } catch (e) {
    
    throw new Error('Não foi possível conectar ao servidor. Verifique se a API está rodando.');
  }

  // 204 = sucesso sem corpo (é o que o DELETE devolve)
  if (response.status === 204) {
    return null;
  }

  // Tenta ler o JSON da resposta
  let data = null;
  try {
    data = await response.json();
  } catch (e) {
    data = null;
  }

  // response.ok é false para status 400, 404, 500...
  if (!response.ok) {
    if (data && data.error) {
      throw new Error(data.error); // mensagem que a API enviou
    }
    throw new Error('Ocorreu um erro inesperado. Tente novamente.');
  }

  return data;
}

const UserApi = {
  list: function () {
    return request(API_URL);
  },
  create: function (user) {
    return request(API_URL, { method: 'POST', body: JSON.stringify(user) });
  },
  update: function (id, user) {
    return request(API_URL + '/' + id, { method: 'PATCH', body: JSON.stringify(user) });
  },
  remove: function (id) {
    return request(API_URL + '/' + id, { method: 'DELETE' });
  },
};
