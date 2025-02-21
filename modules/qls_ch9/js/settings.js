console.log('loading settings.js');

const SymbolSDK = await import('../../../js/bundle.web.js');

const PrivateKey = SymbolSDK.core.PrivateKey;
const KeyPair = SymbolSDK.symbol.KeyPair;
const SymbolFacade = SymbolSDK.symbol.SymbolFacade;
const Address = SymbolSDK.symbol.Address;

async function initialize() {
  console.log("Initializing settings.js...");
  let env = await import('/modules/custom/quicklearning_symbol/js/test.env.js');
  const { NODE, epochAdjustment, identifier, generationHash } = env;
  const facade = new SymbolFacade(identifier);

  var inputElement = document.getElementById('edit-multisig-pvtKey');
  if (!inputElement) {
    console.warn("Element 'edit-multisig-pvtKey' not found yet.");
    return;
  }

  inputElement.addEventListener('blur', function() {
    var inputValue = inputElement.value;
    var addressDiv = document.getElementById('symbol_address');
    if (inputValue.length === 64) {
      const privateKey = new PrivateKey(inputValue); 
      const keyPair = new KeyPair(privateKey);
      const publicKey = keyPair.publicKey; 
      const account_address = new Address(
        facade.network.publicKeyToAddress(publicKey)
      );
      if (addressDiv) {
        addressDiv.innerHTML = account_address;
      }
    } else {
        addressDiv.innerHTML = 'confirm private key'; 
    }
  });

  console.log("settings.js initialized successfully.");
}

const observer = new MutationObserver(async (mutations) => {
  var inputElement = document.getElementById('edit-multisig-pvtKey');
  if (inputElement) {
    await initialize();
  }
});

observer.observe(document.body, { childList: true, subtree: true });

document.addEventListener("DOMContentLoaded", async () => {
  await initialize();
});

console.log('loaded settings.js');
// console.log('loading settings.js');

// // import { NODE, epochAdjustment, identifier, networkType, generationHash } from '../../../js/test.env.js';
// const SymbolSDK = await import('../../../js/bundle.web.js');

// const PrivateKey = SymbolSDK.core.PrivateKey;
// const KeyPair = SymbolSDK.symbol.KeyPair;
// const SymbolFacade = SymbolSDK.symbol.SymbolFacade;
// const Address = SymbolSDK.symbol.Address;


// async function initialize() {
//   // var networkType = document.querySelector('input[name="network_type"]:checked');
//   // alert(networkType);

//   let env;
  
//   // if (networkType.value === 'mainnet') {
//   //   env = await import('/modules/custom/quicklearning_symbol/js/main.env.js');
//   // } else if (networkType.value === 'testnet') {
//   //   env = await import('/modules/custom/quicklearning_symbol/js/test.env.js');
//   // } else {
//   //   throw new Error('Invalid network type');
//   // }
//   env = await import('/modules/custom/quicklearning_symbol/js/test.env.js');

//   // 使用例
//   const { NODE, epochAdjustment, identifier, generationHash } = env;
//   const facade = new SymbolFacade(identifier);

//   var inputElement = document.getElementById('edit-multisig-pvtKey');
//   if (!inputElement) {
//     console.error("Element 'edit-multisig-pvtKey' not found");
//     return;
//   }
//   inputElement.addEventListener('blur', function() {
//     var inputValue = inputElement.value;  // 入力された値を取得
//     var addressDiv = document.getElementById('symbol_address');
//     if(inputValue.length==='64'){
//       const privateKey = new PrivateKey(inputValue); 
//       const keyPair = new KeyPair(privateKey);
//       const publicKey = keyPair.publicKey; 
//       const account_address = new Address(
//         facade.network.publicKeyToAddress(publicKey)
//       );
//       if (addressDiv) {
//         addressDiv.innerHTML = account_address;
//       }
//     }else{
//         addressDiv.innerHTML = 'confirm private key'; 
//     }
//   });
// }

// const observer = new MutationObserver(async (mutations, obs) => {
//   var inputElement = document.getElementById('edit-multisig-pvtKey');
//   if (inputElement) {
//     obs.disconnect();  // 一度見つけたら監視を停止
//     await initialize();  // 非同期処理を適切に待つ
//   }
// });

// observer.observe(document.body, { childList: true, subtree: true });
// // initialize();

// console.log('loaded settings.js');
